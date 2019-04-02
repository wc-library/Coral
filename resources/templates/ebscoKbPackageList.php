<?php
    if(empty($page)) {
        $page = 1;
    }
?>

<table id='resource_table' class='dataTable table-striped' style='width:840px'>
    <thead>
        <tr>
            <th><?php echo _("Name"); ?></th>
            <th><?php echo _("Title Count"); ?></th>
            <th><?php echo _("Content Type"); ?></th>
            <th><?php echo _("Imported?"); ?></th>
            <th><?php echo _("Current Holdings"); ?></th>
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
            <td>
                <?php echo $item->titleCount; ?>
                <a href="javascript:void(0);" class="setPackage"
                   data-vendor-id="<?php echo $item->vendorId; ?>"
                   data-package-id="<?php echo $item->packageId; ?>"
                   data-package-name="<?php echo $item->packageName; ?>"><?php echo '('._('view').')'; ?></a>
                <?php if($item->selectedCount != $item->titleCount): ?>
                    <br>
                    <small>(<?php echo $item->selectedCount.' '._('selected'); ?>)</small>
                <?php endif; ?>
            </td>
            <td>
                <?php echo $item->contentType; ?>
            </td>
            <td style="text-align: center;">
                <?php if($item->resource): ?>
                    <?php if($item->selectedCount): ?>
                        <a href="resource.php?resourceID=<?php echo $item->resource->primaryKey; ?>">
                            <i class="fa fa-check text-success" title="<?php echo _('imported in Coral'); ?>"></i>
                        </a>
                    <?php else: ?>
                        <i class="fa fa-exclamation-triangle text-warning" title="Imported but not selected"></i>
                        <a href="ajax_forms.php?action=getEbscoKbRemoveConfirmation&height=700&width=730&modal=true&resourceID=<?php echo $item->resource->primaryKey; ?>&page=<?php echo $page ?>"
                            class="thickbox">
                            <?php echo _('Delete from Coral'); ?>
                        </a>
                    <?php endif; ?>
                <?php elseif ($item->selectedCount): ?>
                    <i class="fa fa-ban text-danger" title="Imported but not selected in EBSCO"></i>
                    <a href="ajax_forms.php?action=getEbscoKbPackageImportForm&height=700&width=730&modal=true&vendorId=<?php echo $item->vendorId; ?>&packageId=<?php echo $item->packageId; ?>"
                       class="thickbox">
                        <?php echo _('Import Package'); ?>
                    </a>
                <?php endif; ?>
            </td>
            <td style="text-align: center;">
                <?php
                    $selectClass = '';
                    $selectText = _('Not Selected');
                    if ($item->selectedCount) {
                        $selectClass = 'btn-success';
                        $selectText = '<i class="fa fa-check"></i>'._('Selected');
                    }
                ?>
                <div class="ebsco-select-dropdown">
                    <a href="javascript:void(0);" class="btn dd-btn <?php echo $selectClass; ?>" onclick="toggleEbscoSelectDropdown('#<?php echo $item->packageId; ?>-dropdown')">
                        <?php echo $selectText; ?> <i class="fa fa-chevron-down"></i>
                    </a>
                    <div class="dd-content" id="<?php echo $item->packageId; ?>-dropdown">
                        <?php if($item->selectedCount): ?>
                            <a href="javascript:void(0);"
                               onclick="setEbscoSelection(false, <?php echo $item->vendorId; ?>, <?php echo $item->packageId; ?>, null, updateSearch.bind(null, <?php echo $page; ?>))">
                                <?php echo _('Deselect Package'); ?>
                            </a>
                            <?php if ($item->resource): ?>
                                <a href="ajax_forms.php?action=getEbscoKbRemoveConfirmation&height=700&width=730&modal=true&vendorId=<?php echo $item->vendorId; ?>&packageId=<?php echo $item->packageId; ?>&resourceID=<?php echo $item->resource->primaryKey; ?>&page=<?php echo $page ?>"
                                   class="thickbox">
                                    <?php echo _('Deselect Package & Delete from Coral'); ?>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="javascript:void(0);"
                               onclick="setEbscoSelection(true, <?php echo $item->vendorId; ?>, <?php echo $item->packageId; ?>, null, updateSearch.bind(null, <?php echo $page; ?>))">
                                <?php echo _('Select Package'); ?>
                            </a>
                            <?php if(!$item->resource): ?>
                                <a href="ajax_forms.php?action=getEbscoKbPackageImportForm&height=700&width=730&modal=true&vendorId=<?php echo $item->vendorId; ?>&packageId=<?php echo $item->packageId; ?>&select=true"
                                   class="thickbox">
                                    <?php echo _('Select & Import Package'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
