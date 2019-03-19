<?php
$resourceID = $_GET['resourceID'];
$resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));
$childrenCount = count($resource->getChildResources());
?>

<div id="div_ebscoKbConfirmDeletion" class="ebsco-layout" style="width:745px;">
    <div id="deleteError"></div>
    <div class="container" style="text-align: center">
        <p class="bigDarkRedText">
            <?php echo _('Are you sure you want to delete the following resource from Coral?'); ?>
        </p>
        <p style="font-size: 1.3em;"><?php echo $resource->titleText ?></p>
        <div class="row" style="padding-top: 2em;">
            <div class="col-4">
                <button class="btn btn-primary" onclick="deleteResource(<?php echo $resourceID; ?>)">
                    <?php echo _('yes, delete resource'); ?>
                </button>
            </div>
            <div class="col-4">
                <?php if($childrenCount > 0): ?>
                    <button class="btn btn-primary" onclick="deleteResourceAndChildren(<?php echo $resourceID; ?>)">
                        <?php echo _("yes, and delete all $childrenCount child records"); ?>
                    </button>
                <?php endif; ?>
            </div>
            <div class="col-4">
                <button class="btn btn-primary ml-1" onclick="tb_remove()">
                    <?php echo _('cancel'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>

    function processAjax(data) {
      $.ajax({
        type: "GET",
        url: "ajax_processing.php",
        cache: false,
        data: jQuery.param(data),
        success: function(html) {
          tb_remove();
          $('#ebscoKbSearchForm').submit();
        },
        error: function(html) {
          $('#deleteError').html(html);
        }
      });
    }

    function deleteResource(id) {
      var data = {
        action: 'deleteResource',
        resourceID: id
      }
      processAjax(data)
    }

    function deleteResourceAndChildren(id) {
      var data = {
        action: 'deleteResourceAndChildren',
        resourceID: id
      }
    }


</script>