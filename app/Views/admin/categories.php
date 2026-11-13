<div class="a-cols"><div class="panel"><table class="data-table"><tr><th>Name</th><th>Slug</th><th>Parent</th><th>Products</th><th></th></tr>
<?php foreach ($items as $c): ?><tr><td><?= $c['parent_id'] ? '&mdash; ' : '' ?><?= e($c['name']) ?> <?= $c['status'] === 'inactive' ? '<span class="status">inactive</span>' : '' ?></td><td><?= e($c['slug']) ?></td><td><?= e($c['parent_name']) ?></td><td><?= (int) $c['product_count'] ?></td>
<td><form method="post" action="<?= e(url('admin/categories/' . $c['id'] . '/delete')) ?>" data-confirm="Delete category?"><?= csrf_field() ?><button class="link">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<div class="panel"><h3>Add category</h3><form class="form" method="post" action="<?= e(url('admin/categories')) ?>"><?= csrf_field() ?>
<label>Name <input name="name" required></label><label>Slug (optional) <input name="slug"></label>
<label>Parent <select name="parent_id"><option value="">None (top level)</option><?php foreach ($parents as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
<label>Sort order <input type="number" name="sort_order" value="0"></label><button class="btn btn-dark">Add</button></form></div></div>
