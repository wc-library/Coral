<table id='resource_table' class='dataTable table-striped' style='width:840px'>
    <thead>
        <tr>
            <th><?php echo _("Name"); ?></th>
            <th><?php echo _("KBID"); ?></th>
            <th><?php echo _("Vendor"); ?></th>
            <th><?php echo _("Content Type"); ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($items as $item): ?>
        <tr>
            <td>
                <?php echo $item->packageName; ?>
            </td>
            <td>
                <?php echo $item->packageId; ?>
            </td>
            <td>
                <?php echo $item->vendorName; ?>
            </td>
            <td>
                <?php echo $item->contentType; ?>
            </td>
            <td style="text-align: center;">
                <button
                    type="button"
                    data-kbid="<?php echo $item->packageId; ?>"
                    data-type="package"
                    style="border-color: rgb(216, 216, 216) rgb(209, 209, 209) rgb(186, 186, 186); border-radius: 4px; border-style: solid; border-width: 1px; padding: 1px 7px 2px;">
                        <?php echo _("Import"); ?>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>