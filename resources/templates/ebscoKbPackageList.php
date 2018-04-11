<table id='resource_table' class='dataTable table-striped' style='width:840px'>
    <thead>
        <tr>
            <th><?php echo _("Name"); ?></th>
            <th><?php echo _("Imported?"); ?></th>
            <th><?php echo _("Title Count"); ?></th>
            <th><?php echo _("Content Type"); ?></th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($items as $item): ?>
        <?php $item->loadResource(); ?>
        <tr>
            <td>
                <?php echo $item->packageName; ?>
                <br>
                <small>(<?php echo $item->vendorName; ?>)</small>
            </td>
            <td style="text-align: center;">
                <?php if($item->resource): ?>
                    <a href="resource.php?resourceID=<?php echo $item->resource->primaryKey; ?>">
                        <i class="fa fa-check text-success" title="<?php echo _('imported in Coral'); ?>"></i>
                    </a>
                <?php endif; ?>
            </td>
            <td>
                <?php echo $item->titleCount; ?>
                <?php if($item->selectedCount != $item->titleCount): ?>
                <br>
                <small>(<?php echo $item->selectedCount.' '._('selected'); ?>)</small>
                <?php endif; ?>
            </td>
            <td>
                <?php echo $item->contentType; ?>
            </td>
            <td style="text-align: center;">
                <button
                        class="setPackage add-button"
                        data-vendor-id="<?php echo $item->vendorId; ?>"
                        data-package-id="<?php echo $item->packageId; ?>"
                        data-package-name="<?php echo $item->packageName; ?>"
                >
                    <?php echo _("View Titles"); ?>
                </button>
            </td>
            <td style="text-align: center;">
                <a
                    href="ajax_forms.php?action=getEbscoKbPackageImportForm&height=700&width=730&modal=true&vendorId=<?php echo $item->vendorId; ?>&packageId=<?php echo $item->packageId; ?>"
                    class="thickbox btn btn-primary">
                    <?php echo _('import'); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
