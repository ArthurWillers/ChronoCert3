<?php

namespace App\Enums;

enum AuditEvent: string
{
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeleted = 'user.deleted';
    case UserCpfChanged = 'user.cpf_changed';
    case UserLoginEmailChanged = 'user.login_email_changed';
    case UserPasswordChanged = 'user.password_changed';
    case UserInvitationSent = 'user.invitation_sent';

    case AffiliationCreated = 'affiliation.created';
    case AffiliationUpdated = 'affiliation.updated';
    case AffiliationActivated = 'affiliation.activated';
    case AffiliationDeactivated = 'affiliation.deactivated';
    case AffiliationSelected = 'affiliation.selected';

    case CourseCreated = 'course.created';
    case CourseUpdated = 'course.updated';
    case CourseInactivated = 'course.inactivated';
    case CourseReactivated = 'course.reactivated';
    case CourseDeleted = 'course.deleted';

    case CategoryCreated = 'category.created';
    case CategoryUpdated = 'category.updated';
    case CategoryInactivated = 'category.inactivated';
    case CategoryReactivated = 'category.reactivated';
    case CategoryDeleted = 'category.deleted';

    case SubmissionCreated = 'submission.created';
    case SubmissionUploadedByCoordinator = 'submission.uploaded_by_coordinator';
    case SubmissionViewed = 'submission.viewed';
    case SubmissionDownloaded = 'submission.downloaded';
    case SubmissionReviewStarted = 'submission.review_started';
    case SubmissionReclassified = 'submission.reclassified';
    case SubmissionApproved = 'submission.approved';
    case SubmissionRejected = 'submission.rejected';
    case SubmissionExported = 'submission.exported';
    case SubmissionPurged = 'submission.purged';

    case ReviewCreated = 'review.created';
    case ReviewCorrected = 'review.corrected';

    /**
     * Obter a área funcional a que o evento pertence.
     */
    public function area(): string
    {
        return match ($this) {
            self::UserCreated,
            self::UserUpdated,
            self::UserDeleted,
            self::UserCpfChanged,
            self::UserLoginEmailChanged,
            self::UserPasswordChanged,
            self::UserInvitationSent => 'identity',

            self::AffiliationCreated,
            self::AffiliationUpdated,
            self::AffiliationActivated,
            self::AffiliationDeactivated,
            self::AffiliationSelected => 'affiliations',

            self::CourseCreated,
            self::CourseUpdated,
            self::CourseInactivated,
            self::CourseReactivated,
            self::CourseDeleted => 'courses',

            self::CategoryCreated,
            self::CategoryUpdated,
            self::CategoryInactivated,
            self::CategoryReactivated,
            self::CategoryDeleted => 'categories',

            self::SubmissionCreated,
            self::SubmissionUploadedByCoordinator,
            self::SubmissionViewed,
            self::SubmissionDownloaded,
            self::SubmissionReviewStarted,
            self::SubmissionReclassified,
            self::SubmissionApproved,
            self::SubmissionRejected,
            self::SubmissionExported,
            self::SubmissionPurged => 'submissions',

            self::ReviewCreated,
            self::ReviewCorrected => 'reviews',
        };
    }

    /**
     * Obter o rótulo da área funcional em português.
     */
    public function areaLabel(): string
    {
        return match ($this->area()) {
            'identity' => 'Usuários e contas',
            'affiliations' => 'Vínculos',
            'courses' => 'Cursos',
            'categories' => 'Categorias',
            'submissions' => 'Comprovantes',
            default => 'Análises',
        };
    }

    /**
     * Obter o rótulo do evento em português.
     */
    public function label(): string
    {
        return match ($this) {
            self::UserCreated => 'Usuário cadastrado',
            self::UserUpdated => 'Dados do usuário atualizados',
            self::UserDeleted => 'Usuário excluído',
            self::UserCpfChanged => 'CPF alterado',
            self::UserLoginEmailChanged => 'E-mail de login alterado',
            self::UserPasswordChanged => 'Senha alterada',
            self::UserInvitationSent => 'Convite de acesso enviado',
            self::AffiliationCreated => 'Vínculo criado',
            self::AffiliationUpdated => 'Vínculo atualizado',
            self::AffiliationActivated => 'Vínculo ativado',
            self::AffiliationDeactivated => 'Vínculo desativado',
            self::AffiliationSelected => 'Vínculo selecionado',
            self::CourseCreated => 'Curso criado',
            self::CourseUpdated => 'Curso atualizado',
            self::CourseInactivated => 'Curso inativado',
            self::CourseReactivated => 'Curso reativado',
            self::CourseDeleted => 'Curso excluído',
            self::CategoryCreated => 'Categoria criada',
            self::CategoryUpdated => 'Categoria atualizada',
            self::CategoryInactivated => 'Categoria inativada',
            self::CategoryReactivated => 'Categoria reativada',
            self::CategoryDeleted => 'Categoria excluída',
            self::SubmissionCreated => 'Comprovante enviado',
            self::SubmissionUploadedByCoordinator => 'Comprovante registrado pela coordenação',
            self::SubmissionViewed => 'Comprovante consultado',
            self::SubmissionDownloaded => 'Comprovante baixado',
            self::SubmissionReviewStarted => 'Análise iniciada',
            self::SubmissionReclassified => 'Comprovante reclassificado',
            self::SubmissionApproved => 'Comprovante aprovado',
            self::SubmissionRejected => 'Comprovante rejeitado',
            self::SubmissionExported => 'Comprovante exportado',
            self::SubmissionPurged => 'Comprovante expurgado',
            self::ReviewCreated => 'Análise registrada',
            self::ReviewCorrected => 'Análise corrigida',
        };
    }

    /**
     * Obter a cor visual do evento na interface.
     */
    public function color(): string
    {
        return match ($this) {
            self::SubmissionApproved,
            self::AffiliationActivated,
            self::CourseReactivated,
            self::CategoryReactivated => 'green',

            self::SubmissionRejected,
            self::AffiliationDeactivated,
            self::CourseInactivated,
            self::CourseDeleted,
            self::UserDeleted,
            self::CategoryInactivated,
            self::CategoryDeleted,
            self::SubmissionPurged => 'red',

            self::SubmissionViewed,
            self::SubmissionDownloaded,
            self::SubmissionExported => 'blue',

            self::UserPasswordChanged,
            self::UserCpfChanged,
            self::UserLoginEmailChanged => 'yellow',

            default => 'accent',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
