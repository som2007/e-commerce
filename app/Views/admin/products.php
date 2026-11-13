<div class="a-bar"><form method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name / SKU"><button class="btn btn-outline btn-sm">Search</button></form>
<a class="btn btn-dark btn-sm" href="<?= e(url('admin/products/new')) ?>">+ New product</a></div>
<table class="data-table"><tr><th></th><th>Name</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
<?php foreach ($items as $p): ?><tr><td><img src="<?= e(product_image($p['image'])) ?>" width="40" height="50" style="object-fit:cover;border-radius:4px" alt=""></td>
<td><a href="<?= e(url('admin/products/' . $p['id'] . '/edit')) ?>"><?= e($p['name']) ?></a></td><td><?= e($p['sku']) ?></td>
<td><?= money($p['sale_price'] ?: $p['price'], false) ?></td><td><?= (int) $p['stock'] ?></td><td><span class="status"><?= e($p['status']) ?></span></td>
<td><form method="post" action="<?= e(url('admin/products/' . $p['id'] . '/delete')) ?>" data-confirm="Delete this product?"><?= csrf_field() ?><button class="link">Delete</button></form></td></tr><?php endforeach; ?></table>
<?php partial('pagination', ['page' => $page, 'pages' => $pages, 'base' => 'admin/products', 'query' => ['q' => $q]]); ?>
