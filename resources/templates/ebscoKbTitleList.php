<?php
if(empty($page)) {
    $page = 1;
}
?>

<table id='resource_table' class='dataTable table-striped' style='width:840px'>
    <thead>
        <tr>
            <th><?php echo _("Title"); ?></th>
            <th><?php echo _("Resource Type"); ?></th>
            <th><?php echo _("ISXNs"); ?></th>
            <th><?php echo _("Current Status"); ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($items as $item): ?>
        <?php $item->loadResource(); ?>
        <tr>
            <td>
                <a
                    href="ajax_htmldata.php?action=getEbscoKbTitleDetails&height=700&width=730&modal=true&titleId=<?php echo $item->titleId; ?>"
                    class="thickbox">
                    <?php echo $item->titleName; ?>
                </a>
            </td>
            <td>
                <?php echo $item->pubType; ?>
            </td>
            <td>
                <ul style="list-style: none; font-size: .8em">
                    <?php
                    foreach($item->isxnList as $identifier){
                        if(in_array($identifier['type'], [0,1])) {
                            switch($identifier['subtype']){
                                case 1:
                                    $subtype = ' (Print)';
                                    break;
                                case 2:
                                    $subtype = ' (Electronic)';
                                    break;
                                default:
                                    $subtype = '';
                            }
                            echo sprintf('<li style="white-space: nowrap">%s%s</li>', $identifier['id'], $subtype);
                        }
                    }
                    ?>
                </ul>
            </td>
            <td style="text-align: center;">
                <?php if($item->selected): ?>
                    <span class="text-success"><i class="fa fa-check"></i>
                        <?php
                            if($item->resource){
                                echo _('Selected & Imported');
                            } else {
                                echo _('Selected');
                            }
                        ?>
                    </span>
                <?php else: ?>
                    <?php if($item->resource): ?>
                        <span class="text-warning"><i class="fa fa-warning"></i>
                            <?php echo _('Imported but Not Selected'); ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td style="text-align: center;">
                <a
                    href="ajax_htmldata.php?action=getEbscoKbTitleDetails&height=700&width=730&modal=true&titleId=<?php echo $item->titleId; ?>&page=<?php echo $page; ?>"
                    class="thickbox btn btn-primary">
                    <?php echo _('manage'); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>