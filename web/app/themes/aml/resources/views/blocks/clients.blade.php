<?php
use Custom\User\IdPal;
/*
* 
*
* clients block template
*
*/

// create id attribute allowing for custom "anchor" value.
$id = 'clients-' . $block['id'];
if( !empty($block['anchor']) ) {
    $id = $block['anchor'];
}

// create class attribute allowing for custom "className" and "align" values.
$className = 'clients';
if( !empty($block['className']) ) {
    $className .= ' ' . $block['className'];
}

// Load values and assign defaults
$introduction = get_field('introduction') ? : 'This is where youll find all your clients.';

$args = array(
    'role'    => 'client',
    'orderby' => 'user_nicename',
    'order'   => 'ASC'
);
$users = get_users( $args );
?>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
	<? if ( !empty($users) ): ?>
		<?
		IdPal\user_idpal_summary();
		?>
	<? endif; ?>
</div>