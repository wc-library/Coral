<div class="adminRightHeader"><?php echo _("EBSCO Knowledge Base Configuration");?></div>
<form id="ebscoKbConfig">
    <div id="ebscoKbConfigError" class="darkRedText" style="background-color: #F5DDD8;"></div>
    <div style="width: 450px; margin: 10px 0;">
        <label for="ebscoKbEnabled">
            <input type="checkbox" name="enabled" id="ebscoKbEnabled" value="true" <?php echo $config->settings->ebscoKbEnabled == 'Y' ? 'checked' : ''; ?>>
            <?php echo _("Enable EBSCO Knowledge Base"); ?>
        </label>
    </div>
    <div style="width: 160px; margin-right: 20px; display: inline-block;">
        <label for="ebscoKbCustomerId" style="display: block;">Customer ID</label>
        <input type="text" name="customerId" value="<?php echo $config->settings->ebscoKbCustomerId; ?>" id="ebscoKbCustomerId">
    </div>
    <div style="width: 260px; display: inline-block;">
        <label for="ebscoKbApiKey" style="display: block;">Api Key</label>
        <input type="text" name="apiKey" value="<?php echo $config->settings->ebscoKbApiKey; ?>" id="ebscoKbApiKey" style="width: 100%;">
    </div>
    <div style="margin-top: 10px;">
        <button class="btn btn-primary" type="submit" >Save</button>
    </div>
</form>

