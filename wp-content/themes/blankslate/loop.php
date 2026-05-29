<?php $post_thumb = has_post_thumbnail(get_the_ID()) ? get_the_post_thumbnail(get_the_ID(), 'post-grid', array('class' => 'news__item__thumb')) : '';
if(!$post_thumb) {
	$cat = get_the_category(get_the_ID())[0];
	$cat_img = get_field('cat_bg', $cat);
	if($cat_img) $post_thumb = wp_get_attachment_image( $cat_img, 'post-grid', "", array( "alt" => get_the_title(), "class" => 'news__item__thumb' ));
} ?>

<article>
	<a href="<?php the_permalink() ?>" class="news-item">
		<?php if($post_thumb) echo $post_thumb; ?>
		<div class="news-item__info">
			<div class="news-item__date"><?php echo get_the_date(); ?></div>
			<div class="news-item__title"><?php the_title(); ?></div>
			<div class="news-item__excerpt"><?php echo get_the_excerpt(); ?></div>
		</div>
	</a>
</article>
