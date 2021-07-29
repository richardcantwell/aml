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
<?
IdPal\user_idpal_summary();
?>
<style>
.status { width: 15px; height: 15px; display: block; border-radius: 50%; margin: 5px auto 0px auto; }
	.status.light_green { background-color: green; }
</style>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
	<? if ( !empty($users) ): ?>
		<? /*<p><?php echo $introduction; ?></p>*/ ?>
		<? /*<table class="table table-sm table-dark">
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col">ID</th>
					<th scope="col">Name</th>
					<th scope="col">Email</th>
					<th scope="col">Status</th>
					<th scope="col">#</th>
				</tr>
			</thead>
			<tbody>
				<? $i=1;foreach ( $users as $user ): ?>
					<? $status = 'green'; ?>
					<tr>
						<th scope="row"><?=$i?></th>
						<td><?=$user->ID?></td>
						<td><?=esc_html( $user->display_name )?></td>
						<td><?=esc_html( $user->user_email )?></td>
						<td><span class="status light_<?=$status?>"></span></td>
						<td><a href="#" class="btn btn-primary idpal_btn_submit_user" data-id="<?=$user->ID?>">Go</a></td>
					</tr>
				<? $i++;endforeach; ?>
			</tbody>
		</table> */ ?>
	<? endif; ?>
</div>