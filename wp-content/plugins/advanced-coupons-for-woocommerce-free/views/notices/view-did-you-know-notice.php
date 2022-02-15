<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly ?>

<p class="acfw-dyk-notice <?php echo $classname; ?>">
    <span class="title"><?php echo $title; ?></span><br>
    <span class="text"><?php echo $description ?></span>

    <?php if ($button_text && $button_link): ?>
        <a
            class="acfw-button <?php echo esc_attr($button_class); ?>"
            href="<?php echo esc_url($button_link); ?>"
            rel="norefer noopener"
            target="_blank"
        >
            <?php echo $button_text; ?>
        </a>
    <?php endif;?>
</p>
