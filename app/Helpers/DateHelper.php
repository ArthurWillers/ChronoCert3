<?php

namespace App\Helpers {
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    class DateHelper
    {
        public static function format(string|Carbon $date): string
        {
            return Carbon::parse($date)->timezone(config('app.timezone', 'UTC'))->isoFormat('D [de] MMMM [de] YYYY');
        }

        public static function formatShort(string|Carbon $date): string
        {
            return Carbon::parse($date)->timezone(config('app.timezone', 'UTC'))->isoFormat('DD/MM/YYYY');
        }

        public static function formatRelative(string|Carbon $date): string
        {
            return Carbon::parse($date)->timezone(config('app.timezone', 'UTC'))->diffForHumans();
        }

        public static function formatDateTime(string|Carbon $date): string
        {
            return Carbon::parse($date)->timezone(config('app.timezone', 'UTC'))->format('d/m/Y \\à\\s H:i');
        }

        public static function formatMonthYear(string|Carbon $date): string
        {
            return Carbon::parse($date)->timezone(config('app.timezone', 'UTC'))->isoFormat('MM/YYYY');
        }

        public static function formatMonthYearFull(string|Carbon $date): string
        {
            return Str::title(Carbon::parse($date)->timezone(config('app.timezone', 'UTC'))->isoFormat('MMMM YYYY'));
        }
    }
}

namespace {
    use App\Helpers\DateHelper;
    use Carbon\Carbon;

    if (! function_exists('formatDate')) {
        function formatDate(string|Carbon $date): string
        {
            return DateHelper::format($date);
        }
    }

    if (! function_exists('formatShort')) {
        function formatShort(string|Carbon $date): string
        {
            return DateHelper::formatShort($date);
        }
    }

    if (! function_exists('formatDateTime')) {
        function formatDateTime(string|Carbon $date): string
        {
            return DateHelper::formatDateTime($date);
        }
    }

    if (! function_exists('formatRelative')) {
        function formatRelative(string|Carbon $date): string
        {
            return DateHelper::formatRelative($date);
        }
    }

    if (! function_exists('formatMonthYear')) {
        function formatMonthYear(string|Carbon $date): string
        {
            return DateHelper::formatMonthYear($date);
        }
    }

    if (! function_exists('formatMonthYearFull')) {
        function formatMonthYearFull(string|Carbon $date): string
        {
            return DateHelper::formatMonthYearFull($date);
        }
    }
}
