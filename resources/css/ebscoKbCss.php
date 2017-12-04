<style>
    .ebsco-layout .container {
        width: 675px;
        margin: 1em 15px;
    }
    .ebsco-layout .row {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-direction: column;
        flex-direction: column;
        flex-flow: wrap;
    }
    <?php for($i=1; $i<=12; $i++): ?>
    .ebsco-layout .col-<?php echo $i; ?> {
        width: <?php echo $i / 12 * 100; ?>%;
    }
    <?php endfor; ?>

    <?php for($i=1; $i<=5; $i++): ?>
    <?php foreach(['t' => '-top', 'b' => '-bottom', 'l' => '-left', 'r' => '-right', 'a' => ''] as $selector => $ord): ?>
    .ebsco-layout .m<?php echo $selector; ?>-<?php echo $i; ?> {
        margin<?php echo $ord; ?>: <?php echo $i; ?>em;
    }
    .ebsco-layout .p<?php echo $selector; ?>-<?php echo $i; ?> {
        padding<?php echo $ord; ?>: <?php echo $i; ?>em;
    }
    <?php endforeach; ?>

    <?php endfor; ?>

    .ebsco-layout dt {
        font-weight: 700;
        margin-top: 5px;
    }

    .ebsco-layout .card-body {
        -ms-flex: 1 1 auto;
        flex: 1 1 auto;
        padding: 5px;
    }

    .ebsco-layout .card {
        position: relative;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-direction: column;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid rgba(0,0,0,.125);
        border-radius: .25rem;
    }

    .ebsco-layout .card-header {
        padding: .75rem 1.25rem;
        margin-bottom: 0;
        background-color: rgba(0,0,0,.03);
        border-bottom: 1px solid rgba(0,0,0,.125);
    }

    .ebsco-layout .packageOption {
        display: none;
    }

    .ebsco-layout .selectedPackage {
        display: block;
    }

    .ebsco-layout div[id^="providerOption-help"] {
        display: none;
    }

    .ebsco-layout #importError {
        border: 1px solid;
        padding: 1em;
        margin-top: 1em;
        background-color: #f5e7e4;
        border-radius: .25rem;
        display: none;
    }

</style>