/**
 * NS Link - Control Layer JS
 * Scope: timer ring, finish-line reveal, continue/open-link button handling,
 * auto-scroll (final), progress rule.

 * Ad codes yahan nahi hain - containers sirf WP/cPanel se bharte hain..
 */
(function () {
    'use strict';

    if ( typeof window.NSLINK === 'undefined' ) {
        return;
    }

    var cfg = window.NSLINK;
    var layer = document.getElementById( 'nslink-layer' );
    if ( ! layer ) {
        return;
    }

    var CIRC =339.29; /* 2 * Math.PI * 54 */
    var wait = parseInt( layer.getAttribute( 'data-wait' ), 10 );
    if ( isNaN( wait ) || wait < 1 ) {
        wait =8;
    }
    if ( cfg.skip ) {
        wait =1;
    }

    var ringFg = layer.querySelector( '.nslink-ring-fg' );
    var numEl = layer.querySelector( '.nslink-num' );
    var finish = layer.querySelector( '.nslink-finish' );
    var finishArrow = layer.querySelector( '.nslink-finish-arrow' );
    var finishText = layer.querySelector( '.nslink-finish-text' );
    var progress = layer.querySelector( '.nslink-progress span' );
    var btn = document.getElementById( 'nslink-sticky' )
        ? document.querySelector( '#nslink-sticky .nslink-btn' )
        : null;

    /* Finish-line visual copy.. */
    if ( finishArrow ) {
        finishArrow.textContent = cfg.final ? '\u{1F517}' : '\u{2B07}';
    }
    if ( finishText ) {
        finishText.textContent = cfg.finishText;

    }

    var remaining = wait;
    var done =false;

    function setRing( frac ) {
        if ( ringFg ) {
            ringFg.style.strokeDashoffset = String( CIRC * ( 1 - frac ) );
        }
        if ( progress ) {
            progress.style.width = String( ( frac * 100 ) ) + '%';
        }
    }

    function tick() {
        if ( done ) {
            return;
        }
        remaining--;
        if ( numEl ) {
            numEl.textContent = remaining;
        }
        setRing( remaining / wait );

        if ( remaining <=0 ) {
            done =true;
            finishTimer();

        } else {
            window.setTimeout( tick, 1000 );
        }
    }

    function finishTimer() {
        if ( numEl ) {
            numEl.textContent ='0';
        }
        if ( finish ) {
            finish.classList.add( 'show' );
        }
        if ( btn ) {
            btn.disabled =false;
            btn.textContent =cfg.buttonLabel;

        }
        if ( cfg.autoScroll ) {
            var target = btn ? btn : layer;
            if ( target ) {
                window.setTimeout( function () {
                    target.scrollIntoView( { behavior:'smooth', block:'center' } );
                },450 );
            }
        }
    }

    if ( btn ) {
        btn.addEventListener( 'click', function () {
            if ( ! done ) {
                return;

            }
            if ( cfg.buttonTarget ) {
                window.location.href =cfg.buttonTarget;

                return;
            }
            if ( cfg.final ) {
                var url =new URL( window.location.href );
                url.searchParams.set( 'done', '1' );
                url.searchParams.delete( 'next' );

                window.location.href =url.toString();
            }
        } );
    }

    /* Start.. */
    setRing( 1 );
    window.setTimeout( tick, 1000 );

})();
