<?php

use App\Notifications\AccReviewStartedNotification;
use App\Notifications\AccSubmissionApprovedNotification;
use App\Notifications\AccSubmissionRejectedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

test('academic notifications use the student affiliation operational email', function () {
    [$submission, $student, $coordinator, $category] = reviewScenario();
    $review = startAndClassify($this, $submission, $coordinator, $category);

    Notification::assertSentOnDemand(
        AccReviewStartedNotification::class,
        fn (AccReviewStartedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === $student->email,
    );
    Notification::assertSentOnDemandTimes(AccReviewStartedNotification::class, 1);

    reviewActingAs($this, $coordinator)->post(route('reviews.approve', $review))->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(
        AccSubmissionApprovedNotification::class,
        fn (AccSubmissionApprovedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === $student->email
            && $notification->submissionId === $submission->getKey(),
    );
    Notification::assertSentOnDemandTimes(AccSubmissionApprovedNotification::class, 1);
});

test('the rejection notification includes the reason without a private file URL', function () {
    [$submission, $student, $coordinator] = reviewScenario();
    $review = startReview($this, $submission, $coordinator);
    $reason = 'O documento não identifica a carga horária.';

    reviewActingAs($this, $coordinator)
        ->post(route('reviews.reject', $review), ['rejection_reason' => $reason])
        ->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(
        AccSubmissionRejectedNotification::class,
        function (AccSubmissionRejectedNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($student, $reason): bool {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === $student->email
                && $notification->rejectionReason === $reason
                && ! str_contains(implode(' ', $mail->introLines), '/document')
                && ! str_contains(implode(' ', $mail->introLines), '/download');
        },
    );
});
