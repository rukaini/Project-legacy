@unless (app()->environment('testing'))
    @vite('resources/js/learner-tracking.js')
@endunless
