<table id='resource_table' class='dataTable table-striped' style='width:840px'>
    <thead>
        <tr>
            <th><?php echo _("Title"); ?></th>
            <th><?php echo _("KBID"); ?></th>
            <th><?php echo _("Resource Type"); ?></th>
            <th><?php echo _("Import"); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($items as $item): ?>
        <tr>
            <td>
                <?php echo $item->titleName; ?>
            </td>
            <td>
                <?php echo $item->titleId; ?>
            </td>
            <td>
                <?php echo $item->pubType; ?>
            </td>
            <td style="text-align: center;">
                <button
                    type="button"
                    data-kbid="<?php echo $item->titleId; ?>"
                    data-type="title"
                    style="border-color: rgb(216, 216, 216) rgb(209, 209, 209) rgb(186, 186, 186); border-radius: 4px; border-style: solid; border-width: 1px; padding: 1px 7px 2px;">
                        <?php echo _("Import"); ?>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>