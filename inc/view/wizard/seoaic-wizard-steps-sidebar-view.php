<ul class="steps-sidebar position-relative">
    <?php
    if (
        !empty($steps)
        && is_array($steps)
    ) {
        foreach ($steps as $i => $step) {
            $active_class = !empty($step['is_active']) ? ' active' : '';
            $passed_classs = !empty($step['passed']) ? ' passed' : '';
            ?>
            <li class="step position-relative mb-0<?php echo $active_class . $passed_classs;?>">
                <div class="step-titles">
                    <?php
                    if (!empty($step['title'])) {
                        ?>
                        <div class="step-title"><?php echo esc_html($step['title']);?></div>
                        <?php
                    }
                    if (!empty($step['subtitle'])) {
                        ?>
                        <div class="step-subtitle"><?php echo esc_html($step['subtitle']);?></div>
                        <?php
                    }
                    ?>
                </div>
                <div class="step-counter">
                    <span class="counter"><?php echo sprintf('%02d', $i + 1);?></span>
                </div>
                <div class="step-marker"></div>
            </li>
            <?php
        }
    }
    ?>
</ul>