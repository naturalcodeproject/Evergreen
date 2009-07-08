<h3>Testing Models!</h3>

<ul>
<?php foreach ($this->blog_tags as $tag): ?>
    <li><?php echo $tag->get('name'); ?></li>
<?php endforeach; ?>
</ul>