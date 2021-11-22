{{-- @php dynamic_sidebar('sidebar-primary') @endphp --}}
<nav class="col-md-2 d-none d-md-block bg-light sidebar">
	<div class="sidebar-sticky">
		<!--<ul class="nav flex-column">
			<li class="nav-item">
				<a class="nav-link activeX" href="#">Dashboard <span class="sr-only">(current)</span></a>
			</li>
			<li class="nav-item"><a class="nav-link" href="#">Dashboard</a></li>
		</ul> -->
		<div class="stats">
			<?php if ( !empty($data['stats']) ): ?>
				<div class="alert alert-info">
					<ul class="list-unstyled">
						<?php foreach ($data['stats'] as $k=>$v): ?>
							<li><?php echo ucfirst($k) ?>: <?php echo $v ?></li>
						<?php endforeach; ?>
					</ul>
				</div> <!-- .card -->
			<?php endif; ?>
		</div> <!-- .stats -->
		<div class="codes" style="padding: 1rem; width:200px; height: 200px; overflow: scroll;">
			<?php if ( !empty($data['config']) ): ?>
				<table class="table table-dark table-sm" style="font-size: 8px;">
					<thead>
						<tr>
							<th scope="col">#</th>
							<th scope="col">Meaning</th>
							<th scope="col">Desc</th>
							<th scope="col">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php $i=0;foreach ($data['config']['status_codes'] as $code): ?>
						<tr>
							<th><?=$i?></th>
							<td><?=$code['meaning']?></td>
							<td><?=$code['desc']?></td>
							<td><?=$code['action']?></td>
						</tr>
						<?php $i++;endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div> <!-- .codes -->
	</div> <!-- .sidebar-sticky -->
</nav> <!-- .sidebar -->
