<?php $p = $product ?? []; $v = fn ($k, $d = '') => e($p[$k] ?? $d); $action = $product ? 'admin/products/' . $product['id'] : 'admin/products'; ?>
<form class="form" method="post" action="<?= e(url($action)) ?>"><?= csrf_field() ?>
<div class="a-cols"><div>
<div class="panel"><h3>Basics</h3>
<label>Name <input name="name" value="<?= $v('name') ?>" required></label>
<div class="form-row"><label>Slug (optional) <input name="slug" value="<?= $v('slug') ?>"></label><label>SKU <input name="sku" value="<?= $v('sku') ?>"></label></div>
<label>Short description <input name="short_description" value="<?= $v('short_description') ?>"></label>
<label>Description <textarea name="description" rows="4"><?= $v('description') ?></textarea></label></div>
<div class="panel"><h3>Attributes</h3>
<div class="form-row"><label>Colour name <input name="color" value="<?= $v('color') ?>"></label><label>Colour hex (e.g. 1f2d55) <input name="color_hex" value="<?= $v('color_hex') ?>" maxlength="7"></label></div>
<div class="form-row"><label>Style group key (links colours together) <input name="group_key" value="<?= $v('group_key') ?>"></label><label>Badge <input name="badge" value="<?= $v('badge') ?>" placeholder="MOST POPULAR"></label></div>
<div class="form-row"><label>Fabric <input name="fabric" value="<?= $v('fabric') ?>"></label><label>Fit <input name="fit" value="<?= $v('fit') ?>"></label></div>
<div class="form-row"><label>Sleeve <select name="sleeve"><option value="full" <?= ($p['sleeve'] ?? '') === 'full' ? 'selected' : '' ?>>Full</option><option value="half" <?= ($p['sleeve'] ?? '') === 'half' ? 'selected' : '' ?>>Half</option><option value="none" <?= ($p['sleeve'] ?? '') === 'none' ? 'selected' : '' ?>>None (shorts / bottoms)</option></select></label><label>Collar <input name="collar" value="<?= $v('collar') ?>"></label></div>
<label>Occasion <input name="occasion" value="<?= $v('occasion') ?>"></label><label>Care <textarea name="care" rows="2"><?= $v('care') ?></textarea></label></div>
<div class="panel"><h3>Sizes &amp; stock</h3><p class="muted small">Leave stock empty to hide a size. Price/sale override is optional (e.g. plus sizes).</p>
<table class="data-table"><tr><th>Size</th><th>Stock</th><th>Price</th><th>Sale price</th></tr>
<?php foreach ($sizes as $s): $row = $variants[$s] ?? null; ?><tr><td><?= e($s) ?></td>
<td><input type="number" name="v[<?= e($s) ?>][stock]" value="<?= $row ? (int) $row['stock'] : '' ?>" min="0"></td>
<td><input type="number" step="0.01" name="v[<?= e($s) ?>][price]" value="<?= $row ? e($row['price']) : '' ?>"></td>
<td><input type="number" step="0.01" name="v[<?= e($s) ?>][sale]" value="<?= $row && $row['sale_price'] !== null ? e($row['sale_price']) : '' ?>"></td></tr><?php endforeach; ?></table></div>
</div><div>
<div class="panel"><h3>Pricing &amp; status</h3>
<div class="form-row"><label>Price <input type="number" step="0.01" name="price" value="<?= $v('price') ?>" required></label><label>Sale price <input type="number" step="0.01" name="sale_price" value="<?= $v('sale_price') ?>"></label></div>
<label>Status <select name="status"><?php foreach (['active', 'draft', 'inactive'] as $s): ?><option <?= ($p['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
<label class="check"><input type="checkbox" name="is_featured" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Featured</label></div>
<div class="panel"><h3>Collections</h3><p class="muted small">First ticked is the primary category.</p><div class="cat-list">
<?php foreach ($categories as $c): ?><label class="check"><input type="checkbox" name="category_ids[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $catIds, true) ? 'checked' : '' ?>> <?= $c['parent_id'] ? '&mdash; ' : '' ?><?= e($c['name']) ?></label><?php endforeach; ?></div></div>
<button class="btn btn-dark btn-lg btn-block" type="submit">Save product</button></div></div></form>
<?php if ($product): ?><div class="panel"><h3>Images</h3><div class="img-grid">
<?php foreach ($images as $im): ?><div class="img-item"><img src="<?= e(product_image($im['image_path'])) ?>" alt=""><?= $im['is_primary'] ? '<span class="tag">Primary</span>' : '' ?>
<div class="cta-row"><?php if (!$im['is_primary']): ?><form method="post" action="<?= e(url('admin/images/' . $im['id'] . '/primary')) ?>"><?= csrf_field() ?><button class="link small">Make primary</button></form><?php endif; ?>
<form method="post" action="<?= e(url('admin/images/' . $im['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="link small">Delete</button></form></div></div><?php endforeach; ?></div>
<form class="form" method="post" enctype="multipart/form-data" action="<?= e(url('admin/products/' . $product['id'] . '/images')) ?>"><?= csrf_field() ?>
<label>Upload photos (JPG/PNG/WebP, max 6 MB) <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></label><button class="btn btn-outline">Upload</button></form></div><?php endif; ?>
