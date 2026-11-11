@php($commentSearchActive = isset($filters) ? $filters['search'] !== '' : trim(request()->string('search')->toString()) !== '')
<div data-feedback-comment-batch>
    @foreach ($comments as $comment)
        <article class="fb-comment-row">
            <p>{{ $comment['comment'] }}</p>
            <time><i class="bi bi-clock" aria-hidden="true"></i>{{ $comment['date_label'] }}</time>
        </article>
    @endforeach
    @if ($hasMoreComments)
        <button type="button" class="ana-btn ana-btn-secondary fb-show-more" data-feedback-show-more data-next-offset="{{ $nextCommentOffset }}">Show more comments</button>
    @endif
</div>
@if ($commentTotal === 0)
    <p class="fb-no-comments"><i class="bi bi-chat-square-text" aria-hidden="true"></i><span>{{ $commentSearchActive ? 'No comments match your search.' : 'No written comments for this question.' }}</span></p>
@endif
