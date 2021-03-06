<?php

namespace App\Observers;

use App\Post;
use App\PostCite;
use Carbon\Carbon;
use Event;
use App\Events\PostWasAdded;
use App\Events\PostWasDeleted;
use App\Events\PostWasModified;

class PostObserver
{
    /**
     * Handles model after create (non-existant save).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function created(Post $post)
    {
        // Fire event, which clears cache among other things.
        Event::dispatch(new \App\Events\PostWasCreated($post));

        // Add dice rolls.
        // Because of how dice rolls work, we don't ever remove them and only
        // create them with the post, not on update.
        $throws = $post->getDiceFromText();
        $dice   = collect();

        foreach ($throws as $throw)
        {
            $post->dice()->save($throw['throw'], [
                'command_text' => $throw['command_text'],
                'order' => $throw['order'],
            ]);
        }

        // Log staff posts.
        if ($post->capcode_id) {
            Event::dispatch(new \App\Events\PostWasCapcoded($post, user()));
        }

        return true;
    }

    /**
     * Checks if this model is allowed to create (non-existant save).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function creating(Post $post)
    {
        // Reuire board_id to save.
        return isset($post->board_id);
    }

    /**
     * Handles model after delete (pre-existing hard or soft deletion).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function deleted($post)
    {
        // After a post is deleted, update OP's reply count.
        if (!is_null($post->reply_to)) {
            $lastReply = $post->op->getReplyLast();

            if ($lastReply) {
                $post->op->reply_last = $lastReply->created_at;
            } else {
                $post->op->reply_last = $post->op->created_at;
            }

            $post->op->reply_count -= 1;
            $post->op->save();
        }

        // Update any posts that reference this one.
        $citedBy = $post->citedByPosts();
        $citedBy->update([
            'body_parsed' => null,
            'body_parsed_at' => null,
        ]);
        $citedBy->detach();

        // Remove this item from the cache.
        $post->forget();

        // Fire event.
        Event::dispatch(new PostWasDeleted($post));

        return true;
    }

    /**
     * Checks if this model is allowed to delete (pre-existing deletion).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function deleting($post)
    {
        // When deleting a post, delete its children.
        Post::replyTo($post->post_id)->delete();

        // Clear authorshop information.
        $post->author_ip = null;
        $post->author_ip_nulled_at = Carbon::now();
        $post->save();

        return true;
    }

    /**
     * Handles model after save (pre-existing or non-existant save).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function saved(Post $post)
    {
        // Rebuild citation relationships.

        // Clear citations.
        $post->cites()->delete();

        // Readd citations.
        $cited = $post->getCitesFromText();
        $cites = [];

        foreach ($cited['posts'] as $citedPost) {
            $cites[] = new PostCite([
                'post_board_uri' => $post->board_uri,
                'post_board_id' => $post->board_id,
                'cite_id' => $citedPost->post_id,
                'cite_board_uri' => $citedPost->board_uri,
                'cite_board_id' => $citedPost->board_id,
            ]);
        }

        foreach ($cited['boards'] as $citedBoard) {
            $cites[] = new PostCite([
                'post_board_uri' => $post->board_uri,
                'post_board_id' => $post->board_id,
                'cite_board_uri' => $citedBoard->board_uri,
            ]);
        }

        if (count($cites) > 0) {
            $post->cites()->saveMany($cites);
        }

        return true;
    }

    /**
     * Checks if this model is allowed to save (pre-existing or non-existant save).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function saving(Post $post)
    {
        // Reuire board_id to save.
        return isset($post->board_id);
    }

    /**
     * Handles model after update (pre-existing save).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function updated(Post $post)
    {
        // Fire event, which clears cache among other things.
        Event::dispatch(new PostWasModified($post));

        return true;
    }

    /**
     * Checks if this model is allowed to update (pre-existing save).
     *
     * @param \App\Post $post
     *
     * @return bool
     */
    public function updating(Post $post)
    {
        return true;
    }
}
