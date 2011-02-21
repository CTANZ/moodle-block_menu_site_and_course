<?PHP
/**
 * Function to format the section summary html into a link for the Course Menu
 * It will handle p and heading tags and line breaks
 *
 * re-written by Bruce Webster, University of Canterbury.
 */
function truncate_description($string) {
    if(!($out=_trunc_ds($string))) {
        if(preg_match('/.+alt ?= ?"([^"]+)"/i',$string,$m)) return _trunc_ds($m[1]);
    }
    return $out;
}


mb_internal_encoding("UTF-8");

function _trunc_ds ($string, $lines=2) {
    global $THEME;

    $br='-+*br*+-';  // temp placeholders for br, h and p tags
    $hp='-+*hp*+-';

    $string=html_entity_decode(trim(preg_replace("/(&nbsp;|\s)+/", " ",
        strip_tags(preg_replace("/(\s?<br\s*\/?>\s?)+/i", " $br ", 
        preg_replace("/(\s?(<\/?p>|<\/?h[0-9]>)\s?)+/"," $hp ",$string))))),ENT_NOQUOTES,'UTF-8');

    $words=explode(' ',$string); $len=0; $out='';

    foreach($words as $w) {
        switch ($w) {
        case $br: 
            if($out) {
                if(--$lines<1) break 2;
                $out.='<br />'; $len=0;
            }
            break;
        case $hp: break ($out? 2:1);
        default:
            while(mb_strlen($w) > ($ch=$THEME->navmenuwidth-$len)) {
                if($len && --$lines) {$ch+=$len; $len=0;}     //self-break on space

                if(mb_strlen($w) > $ch) {
                    $out=($ch < 2? trim($out):$out).mb_substr($w,0,--$ch).(--$lines > 0? '-':'&hellip;');
                    $w=mb_substr($w,$ch);
                }
                if($lines < 1) break 3;
            }
            $out.=$w.' '; $len+=mb_strlen($w)+1;
        }
    }
    return trim($out);
}
?>