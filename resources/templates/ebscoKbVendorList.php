<table id='resource_table' class='dataTable table-striped' style='width:840px'>
    <thead>
        <tr>
            <th><?php echo _("Name"); ?></th>
            <th><?php echo _("KBID"); ?></th>
            <th><?php echo _("Packages"); ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($items as $item): ?>
        <tr>
            <td>
                <?php echo $item->vendorName; ?>
            </td>
            <td>
                <?php echo $item->vendorId; ?>
            </td>
            <td>
                <?php echo $item->packagesTotal; ?> (<?php echo $item->packagesSelected; ?> selected)
            </td>
            <td style="text-align: center;">
                <a
                    href="http://coral.dev/resources/ajax_htmldata.php?action=getEbscoKbVendorInfo&height=700&width=730&modal=true&vendorId=<?php echo $item->vendorId; ?>"
                    class="thickbox">
                    <?php echo _("View Packages"); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>