<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<div class="acfw-help-video-gallery">
    <div class="videos">
    <?php foreach ($video_embeds as $data): ?>
        <div class="help-video" data-videoid="<?php echo esc_attr($data['video_id']); ?>">
            <?php echo $data['embed']; ?>
        </div>
    <?php endforeach;?>
    </div>
    <ul class="thumbnails">
        <li><img src="<?php echo esc_attr($data['thumbnail']); ?>" /></li>
    </ul>
</div>