@extends('layouts.main.panel')

@section('body')
<div class="attachment">
    {!! $file->getThumbnailHtml() !!}
</div>

<form method="POST" action="{{ route('panel.site.files.delete', $file->hash) }}">
    @method('DELETE')
    @csrf
    <button name="action" value="delete">@lang('board.action.delete_global')</button>
    <button name="action" value="ban">@lang('board.action.ban_delete_global')</button>
    <button name="action" value="fuzzyban">@lang('board.action.fuzzyban')</button>
</form>

<table>
    <thead>
        <tr>
            <th style="max-width: 14em;"></th>
            <th style="max-width: 8em;"></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($file->posts as $post)
        <tr class="@if ($post->isDeleted()) row-inactive @endif">
            <td>/{{ $post->board_uri }}/</td>
            <td>{{ $post->created_at->diffForHumans() }}</td>
            <td class="attachments">
                @foreach ($post->attachments as $attachment)
                <a class="attachment" href="{{ route('panel.site.files.show', $attachment->hash) }}" style="height: 100px; width: 100px;">
                    {!! $attachment->getThumbnailHtml($post->board, 100) !!}
                </a>
                @endforeach
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
