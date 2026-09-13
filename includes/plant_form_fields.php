<!-- Shared plant form fields — included inside both Add and Edit modals -->
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Plant Name <span class="text-danger">*</span></label>
            <input type="text" name="plant_name" class="form-control" required maxlength="150"
                   placeholder="e.g. Peace Lily">
            <div class="invalid-feedback">Plant name is required.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Botanical Name</label>
            <input type="text" name="botanical_name" class="form-control" maxlength="200"
                   placeholder="e.g. Spathiphyllum wallisii">
        </div>
        <div class="col-md-4">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
                <option value="">Select category</option>
                <option value="indoor">Indoor</option>
                <option value="outdoor">Outdoor</option>
                <option value="ornamental">Ornamental</option>
                <option value="edible">Edible</option>
            </select>
            <div class="invalid-feedback">Please select a category.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Price (LKR) <span class="text-danger">*</span></label>
            <input type="number" name="price" class="form-control" required min="0" step="0.01"
                   placeholder="0.00">
            <div class="invalid-feedback">Please enter a valid price.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
            <input type="number" name="stock_quantity" class="form-control" required min="0" step="1"
                   placeholder="0">
            <div class="invalid-feedback">Please enter stock quantity.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"
                      placeholder="Brief description of the plant…"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Care Instructions</label>
            <textarea name="care_instructions" class="form-control" rows="3"
                      placeholder="Watering schedule, light requirements, tips…"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Plant Image <small class="text-muted">(JPG/PNG/WebP, max 2MB)</small></label>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                  <input type="file" name="image" class="form-control" id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>ImageInput"
                       accept="image/jpeg,image/png,image/webp,image/gif" style="flex:1;min-width:0;">
                <!-- Hidden flag sent to server when user wants to clear the existing image -->
                <input type="hidden" name="remove_image" id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>RemoveImageFlag" value="0">
                <button type="button" id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>RemoveImageBtn"
                        class="btn btn-outline-danger btn-sm d-none"
                        title="Remove selected image">
                    <i class="bi bi-x-lg me-1"></i>Remove Image
                </button>
            </div>
            <!-- Preview of newly chosen image -->
              <div id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>ImagePreviewWrap" class="mt-2 d-none">
                 <img id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>ImagePreview" src="" alt="Preview"
                     class="rounded border" style="display:block;width:180px;height:120px;object-fit:contain;background:#f8faf9;">
                 <div class="small text-muted mt-1" id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>ImageFileName"></div>
                 <a id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>ImageUrl" href="#" target="_blank"
                    rel="noopener" class="small d-none">Open image</a>
            </div>
            <!-- Badge shown in Edit modal when a current image exists -->
            <div id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>CurrentImageBadge" class="mt-2 d-none">
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-image me-1"></i><span id="<?= htmlspecialchars($plantFormId ?? 'plant') ?>CurrentImageName">Current image kept</span>
                </span>
            </div>
        </div>
    </div>
</div>

