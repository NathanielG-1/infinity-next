<section id="site-recent-images" class="grid-50 tablet-grid-50 mobile-grid-50 {{ $rtl ? 'push-50' : ''}}">
    <div class="smooth-box">
        <h2 class="index-title">@lang('index.title.recent_images')</h2>
        <ul class="recent-images selfclear">
            @foreach (\App\PostAttachment::getRecentImages(30, false) as $file)
                @if ($file->storage->hasThumb())
                <li class="recent-image {{ $file->post->board->isWorksafe() ? 'sfw' : 'nsfw' }}">
                    <a class="recent-image-link" href="{{ $file->post->getURL() }}">
                        {!! $file->storage->getThumbnailHtml($file->post->board, 116) !!}
                    </a>
                </li>
                @endif
            @endforeach
        </ul>
    </div>
</section>
