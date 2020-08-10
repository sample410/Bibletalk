<div class="update-nag breaking-changes-nudge" style="padding:11px 15px; margin:5px 15px 2px 0;">
    <h2>PeepSo 3 is near - <u>breaking changes</u> are coming!</h2>

    <p>
        <b>We are coming closer to a new major release, which will be PeepSo 3.0.0.0</b> (previously known as 2.8.0). It's the biggest release in the 5 years of PeepSo history and will ship a new notification engine, group category streams and a brand new design.
    </p>

    <p>
        Unfortunately we also have to <b>break some backward compatibility</b>, especially considering the HTML and CSS
    </p>

    <h2>Use a staging site to test future PeepSo updates</h2>

    <p>
        Despite our best intentions, it's virtually impossible for us to test absolutely everything, <b>we respectfully request you to prepare your environment accordingly</b>.
    </p>

    <p>
        Please <b>start using staging sites to test updates</b> - especially because PeepSo 3 will ship <b>BIG BREAKING CHANGES</b>:


    <ol>
        <li>
            <b>New HTML and CSS</b> to support a major redesign - <b>custom CSS, template overrides and child themes WILL stop working</b>. There might be unforeseen layout issues with third parties.
        </li>

        <li>
            <b>New customizer - some PeepSo appearance options WILL stop working</b>. There might be a need to do some configuration/appearance work after upgrading.
        </li>

        <li>
            <b>PHP 7</b> will be required, PeepSo plugins and Gecko theme <b>WILL NOT ACTIVATE on PHP 5</b>.
        </li>
    </ol>

    </p>

    <h3><a href="https://peep.so/three" target="_blank">Read more on PeepSo.com</a></h3>

    <p style="float:right">
        <a id="ps-gs-notice-dismiss" href="#" class="button ps-js-breaking-changes-nudge-no">Remind me later</a>
        <a id="ps-gs-notice-hide-permanently" href="#" class="button button-primary button ps-js-breaking-changes-nudge-hide">Don't show this again</a>
    </p>

    <?php if($was_asked) { ?>
        <!-- This user was last asked <?php echo $last_nudge;?> -->
    <?php } else { ?>
        <!-- This user was last asked <?php echo $last_nudge;?> -->
    <?php } ?>
</div>
<script>
    setTimeout(function() {
        jQuery(function( $ ) {
            $( '.ps-js-breaking-changes-nudge-no' ).on( 'click', function( e ) {
                e.preventDefault();
                e.stopPropagation();
                $( this ).closest( '.breaking-changes-nudge' ).remove();
                $.get( window.location.href, { peepso_hide_breaking_changes_nudge: 1 } );
            });
            $( '.ps-js-breaking-changes-nudge-hide' ).on( 'click', function( e ) {
                e.preventDefault();
                e.stopPropagation();
                $( this ).closest( '.breaking-changes-nudge' ).remove();
                $.get( window.location.href, { peepso_hide_permanently_breaking_changes_nudge: 1 } );
            });
        });
    }, 100 );
</script>
