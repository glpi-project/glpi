<?php

/**
 *  UrlLinker - facilitates turning plain text URLs into HTML links.
 *
 *  Author: Søren Løvborg
 *
 *  To the extent possible under law, Søren Løvborg has waived all copyright
 *  and related or neighboring rights to UrlLinker.
 *  http://creativecommons.org/publicdomain/zero/1.0/
 */
global $rexUrlLinker, $validTlds;
/*
 *  Regular expression bits used by htmlEscapeAndLinkUrls() to match URLs.
 */
$rexScheme    = 'https?://';
// $rexScheme    = "$rexScheme|ftp://"; // Uncomment this line to allow FTP addresses.
$rexDomain    = '(?:[-a-zA-Z0-9\x7f-\xff]{1,63}\.)+[a-zA-Z\x7f-\xff][-a-zA-Z0-9\x7f-\xff]{1,62}';
$rexIp        = '(?:[1-9][0-9]{0,2}\.|0\.){3}(?:[1-9][0-9]{0,2}|0)';
$rexPort      = '(:[0-9]{1,5})?';
$rexPath      = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
$rexQuery     = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
$rexFragment  = '(#[!$-/0-9?:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
$rexUsername  = '[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64}';
$rexPassword  = $rexUsername; // allow the same characters as in the username
$rexUrl       = "($rexScheme)?(?:($rexUsername)(:$rexPassword)?@)?($rexDomain|$rexIp)($rexPort$rexPath$rexQuery$rexFragment)";
$rexTrailPunct= "[)'?.!,;:]"; // valid URL characters which are not part of the URL if they appear at the very end
$rexNonUrl    = "[^-_#$+.!*%'(),;/?:@=&a-zA-Z0-9\x7f-\xff]"; // characters that should never appear in a URL
$rexUrlLinker = "{\\b$rexUrl(?=$rexTrailPunct*($rexNonUrl|$))}";
$rexUrlLinker .= 'i'; // Uncomment this line to allow uppercase URL schemes (e.g. "HTTP://google.com").

/**
 *  $validTlds is an associative array mapping valid TLDs to the value true.
 *  Since the set of valid TLDs is not static, this array should be updated
 *  from time to time.
 *
 *  List source:  http://data.iana.org/TLD/tlds-alpha-by-domain.txt
 *  Last updated: 2014-06-05
 */
$validTlds = array_fill_keys(explode(" ", ".ac .academy .accountants .actor .ad .ae .aero .af .ag .agency .ai .airforce .al .am .an .ao .aq .ar .archi .army .arpa .as .asia .associates .at .attorney .au .audio .autos .aw .ax .axa .az .ba .bar .bargains .bayern .bb .bd .be .beer .berlin .best .bf .bg .bh .bi .bid .bike .bio .biz .bj .black .blackfriday .blue .bm .bn .bo .boutique .br .bs .bt .build .builders .buzz .bv .bw .by .bz .ca .cab .camera .camp .capital .cards .care .career .careers .cash .cat .catering .cc .cd .center .ceo .cf .cg .ch .cheap .christmas .church .ci .citic .ck .cl .claims .cleaning .clinic .clothing .club .cm .cn .co .codes .coffee .college .cologne .com .community .company .computer .condos .construction .consulting .contractors .cooking .cool .coop .country .cr .credit .creditcard .cruises .cu .cv .cw .cx .cy .cz .dance .dating .de .degree .democrat .dental .dentist .desi .diamonds .digital .directory .discount .dj .dk .dm .dnp .do .domains .dz .ec .edu .education .ee .eg .email .engineer .engineering .enterprises .equipment .er .es .estate .et .eu .eus .events .exchange .expert .exposed .fail .farm .feedback .fi .finance .financial .fish .fishing .fitness .fj .fk .flights .florist .fm .fo .foo .foundation .fr .frogans .fund .furniture .futbol .ga .gal .gallery .gb .gd .ge .gf .gg .gh .gi .gift .gives .gl .glass .globo .gm .gmo .gn .gop .gov .gp .gq .gr .graphics .gratis .gripe .gs .gt .gu .guide .guitars .guru .gw .gy .hamburg .haus .hiphop .hiv .hk .hm .hn .holdings .holiday .homes .horse .host .house .hr .ht .hu .id .ie .il .im .immobilien .in .industries .info .ink .institute .insure .int .international .investments .io .iq .ir .is .it .je .jetzt .jm .jo .jobs .jp .juegos .kaufen .ke .kg .kh .ki .kim .kitchen .kiwi .km .kn .koeln .kp .kr .kred .kw .ky .kz .la .land .lawyer .lb .lc .lease .li .life .lighting .limited .limo .link .lk .loans .london .lr .ls .lt .lu .luxe .luxury .lv .ly .ma .maison .management .mango .market .marketing .mc .md .me .media .meet .menu .mg .mh .miami .mil .mk .ml .mm .mn .mo .mobi .moda .moe .monash .mortgage .moscow .motorcycles .mp .mq .mr .ms .mt .mu .museum .mv .mw .mx .my .mz .na .nagoya .name .navy .nc .ne .net .neustar .nf .ng .nhk .ni .ninja .nl .no .np .nr .nu .nyc .nz .okinawa .om .onl .org .pa .paris .partners .parts .pe .pf .pg .ph .photo .photography .photos .pics .pictures .pink .pk .pl .plumbing .pm .pn .post .pr .press .pro .productions .properties .ps .pt .pub .pw .py .qa .qpon .quebec .re .recipes .red .rehab .reise .reisen .ren .rentals .repair .report .republican .rest .reviews .rich .rio .ro .rocks .rodeo .rs .ru .ruhr .rw .ryukyu .sa .saarland .sb .sc .schule .sd .se .services .sexy .sg .sh .shiksha .shoes .si .singles .sj .sk .sl .sm .sn .so .social .software .sohu .solar .solutions .soy .space .sr .st .su .supplies .supply .support .surgery .sv .sx .sy .systems .sz .tattoo .tax .tc .td .technology .tel .tf .tg .th .tienda .tips .tirol .tj .tk .tl .tm .tn .to .today .tokyo .tools .town .toys .tp .tr .trade .training .travel .tt .tv .tw .tz .ua .ug .uk .university .uno .us .uy .uz .va .vacations .vc .ve .vegas .ventures .versicherung .vet .vg .vi .viajes .villas .vision .vn .vodka .vote .voting .voto .voyage .vu .wang .watch .webcam .website .wed .wf .wien .wiki .works .ws .wtc .wtf .xn--3bst00m .xn--3ds443g .xn--3e0b707e .xn--45brj9c .xn--4gbrim .xn--55qw42g .xn--55qx5d .xn--6frz82g .xn--6qq986b3xl .xn--80adxhks .xn--80ao21a .xn--80asehdb .xn--80aswg .xn--90a3ac .xn--c1avg .xn--cg4bki .xn--clchc0ea0b2g2a9gcd .xn--czr694b .xn--czru2d .xn--d1acj3b .xn--fiq228c5hs .xn--fiq64b .xn--fiqs8s .xn--fiqz9s .xn--fpcrj9c3d .xn--fzc2c9e2c .xn--gecrj9c .xn--h2brj9c .xn--i1b6b1a6a2e .xn--io0a7i .xn--j1amh .xn--j6w193g .xn--kprw13d .xn--kpry57d .xn--l1acc .xn--lgbbat1ad8j .xn--mgb9awbf .xn--mgba3a4f16a .xn--mgbaam7a8h .xn--mgbab2bd .xn--mgbayh7gpa .xn--mgbbh1a71e .xn--mgbc0a9azcg .xn--mgberp4a5d4ar .xn--mgbx4cd0ab .xn--ngbc5azd .xn--nqv7f .xn--nqv7fs00ema .xn--o3cw4h .xn--ogbpf8fl .xn--p1ai .xn--pgbs0dh .xn--q9jyb4c .xn--rhqv96g .xn--s9brj9c .xn--ses554g .xn--unup4y .xn--wgbh1c .xn--wgbl6a .xn--xkc2dl3a5ee0h .xn--xkc2al3hye2a .xn--yfro4i67o .xn--ygbi2ammx .xn--zfr164b .xxx .xyz .yachts .ye .yokohama .yt .za .zm .zw .zone"), true);

/**
 *  Transforms plain text into valid HTML, escaping special characters and
 *  turning URLs into links.
 */
function htmlEscapeAndLinkUrls($text)
{
    global $rexUrlLinker, $validTlds;

    $html = '';

    $position = 0;
    while (preg_match($rexUrlLinker, $text, $match, PREG_OFFSET_CAPTURE, $position))
    {
        list($url, $urlPosition) = $match[0];

        // Add the text leading up to the URL.
        $html .= htmlspecialchars(substr($text, $position, $urlPosition - $position));

        $scheme      = $match[1][0];
        $username    = $match[2][0];
        $password    = $match[3][0];
        $domain      = $match[4][0];
        $afterDomain = $match[5][0]; // everything following the domain
        $port        = $match[6][0];
        $path        = $match[7][0];

        // Check that the TLD is valid or that $domain is an IP address.
        $tld = strtolower(strrchr($domain, '.'));
        if (preg_match('{^\.[0-9]{1,3}$}', $tld) || isset($validTlds[$tld]))
        {
            // Do not permit implicit scheme if a password is specified, as
            // this causes too many errors (e.g. "my email:foo@example.org").
            if (!$scheme && $password)
            {
                $html .= htmlspecialchars($username);

                // Continue text parsing at the ':' following the "username".
                $position = $urlPosition + strlen($username);
                continue;
            }

            if (!$scheme && $username && !$password && !$afterDomain)
            {
                // Looks like an email address.
                $completeUrl = "mailto:$url";
                $linkText = $url;
            }
            else
            {
                // Prepend http:// if no scheme is specified
                $completeUrl = $scheme ? $url : "http://$url";
                $linkText = "$domain$port$path";
            }

            $linkHtml = '<a href="' . htmlspecialchars($completeUrl) . '">'
                . htmlspecialchars($linkText)
                . '</a>';

            // Cheap e-mail obfuscation to trick the dumbest mail harvesters.
            $linkHtml = str_replace('@', '&#64;', $linkHtml);

            // Add the hyperlink.
            $html .= $linkHtml;
        }
        else
        {
            // Not a valid URL.
            $html .= htmlspecialchars($url);
        }

        // Continue text parsing from after the URL.
        $position = $urlPosition + strlen($url);
    }

    // Add the remainder of the text.
    $html .= htmlspecialchars(substr($text, $position));
    return $html;
}

/**
 * Turns URLs into links in a piece of valid HTML/XHTML.
 *
 * Beware: Never render HTML from untrusted sources. Rendering HTML provided by
 * a malicious user can lead to system compromise through cross-site scripting.
 */
function linkUrlsInTrustedHtml($html)
{
    $reMarkup = '{</?([a-z]+)([^"\'>]|"[^"]*"|\'[^\']*\')*>|&#?[a-zA-Z0-9]+;|$}';

    $insideAnchorTag = false;
    $position = 0;
    $result = '';

    // Iterate over every piece of markup in the HTML.
    while (true)
    {
        preg_match($reMarkup, $html, $match, PREG_OFFSET_CAPTURE, $position);

        list($markup, $markupPosition) = $match[0];

        // Process text leading up to the markup.
        $text = substr($html, $position, $markupPosition - $position);

        // Link URLs unless we're inside an anchor tag.
        if (!$insideAnchorTag) $text = htmlEscapeAndLinkUrls($text);

        $result .= $text;

        // End of HTML?
        if ($markup === '') break;

        // Check if markup is an anchor tag ('<a>', '</a>').
        if ($markup[0] !== '&' && $match[1][0] === 'a')
            $insideAnchorTag = ($markup[1] !== '/');

        // Pass markup through unchanged.
        $result .= $markup;

        // Continue after the markup.
        $position = $markupPosition + strlen($markup);
    }
    return $result;
}