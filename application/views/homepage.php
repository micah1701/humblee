<?php
Draw::content($content, "pagebody");
?>
<hr>

<section class="fixed-grid has-1-cols has-2-cols-desktop content">
    <div class="grid">

        <?php
        Draw::content($content, "pagebody_2");
        Draw::content($content, "pagebody_3");
        ?>

    </div>
</section>