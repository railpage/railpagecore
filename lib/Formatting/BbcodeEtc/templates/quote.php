<blockquote class="bs-callout bs-callout-info">
    <?php if (!empty($author) || !empty($date)) { ?>
        <div class="decoda-quote-head">
            <?php if (!empty($date)) { ?>
                <span class="decoda-quote-date">
                    <?php echo date($dateFormat, is_numeric($date) ? $date : strtotime($date)); ?>
                </span>
            <?php } ?>

            <span class="clear"></span>
        </div>
    <?php } ?>

    <div class="decoda-quote-body">
        <?php echo $content; ?>
    </div>
    
    <?php if (!empty($author)) { ?>
        <footer>
            <cite>
            <?php echo $this->escape($author); ?>
            </cite>
        </footer>
    <?php } ?>
</blockquote>