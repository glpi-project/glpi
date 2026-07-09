<?php

/**
 * Default content of notification templates that were affected by the
 * HTML-escaping issue reported in #23464 (content_html/content_text
 * stored HTML-encoded in `install/empty_data.php`, never decoded back
 * because `must_unsanitize_db_data` is disabled by default on new
 * installations since GLPI 11.0.5, see #22658).
 *
 * Only rows whose content still exactly matches the broken default value
 * are fixed: this guarantees templates customized by the administrator
 * are never touched.
 */
return [
    [
        'notificationtemplates_id' => 1,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;',
        'new'                      => '<p>##lang.dbconnection.delay## : ##dbconnection.delay##</p>',
    ],
    [
        'notificationtemplates_id' => 2,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } --&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.user##:&lt;/span&gt;##reservation.user##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.item.name##:&lt;/span&gt;##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.end##:&lt;/span&gt;##reservation.end##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##&lt;/p&gt;',
        'new'                      => '<!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } -->
<p><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.reservation.user##:</span>##reservation.user##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.reservation.item.name##:</span>##reservation.itemtype## - ##reservation.item.name##<br />##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.reservation.begin##:</span> ##reservation.begin##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.reservation.end##:</span>##reservation.end##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.reservation.comment##:</span> ##reservation.comment##</p>',
    ],
    [
        'notificationtemplates_id' => 3,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.reservation.entity## : ##reservation.entity## &lt;br /&gt; &lt;br /&gt;
##FOREACHreservations## &lt;br /&gt;##lang.reservation.itemtype## :  ##reservation.itemtype##&lt;br /&gt;
 ##lang.reservation.item## :  ##reservation.item##&lt;br /&gt; &lt;br /&gt;
 &lt;a href="##reservation.url##"&gt; ##reservation.url##&lt;/a&gt;&lt;br /&gt;
 ##ENDFOREACHreservations##&lt;/p&gt;',
        'new'                      => '<p>##lang.reservation.entity## : ##reservation.entity## <br /> <br />
##FOREACHreservations## <br />##lang.reservation.itemtype## :  ##reservation.itemtype##<br />
 ##lang.reservation.item## :  ##reservation.item##<br /> <br />
 <a href="##reservation.url##"> ##reservation.url##</a><br />
 ##ENDFOREACHreservations##</p>',
    ],
    [
        'notificationtemplates_id' => 4,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    --&gt;
&lt;div&gt;##IFticket.storestatus=5##&lt;/div&gt;
&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.urlapprove##"&gt;##ticket.urlapprove##&lt;/a&gt; &lt;strong&gt;&#160;&lt;/strong&gt;&lt;/div&gt;
&lt;div&gt;&lt;strong&gt;##lang.ticket.autoclosewarning##&lt;/strong&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.ticket.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##ticket.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.ticket.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.ticket.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.description## ##ENDIFticket.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEticket.storestatus## ##lang.ticket.url## : &lt;a href="##ticket.url##"&gt;##ticket.url##&lt;/a&gt; ##ENDELSEticket.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.ticket.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.authors##&lt;/span&gt;&#160;:##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors##    ##ELSEticket.authors##--##ENDELSEticket.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.creationdate##&lt;/span&gt;&#160;:##ticket.creationdate## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.closedate##&lt;/span&gt;&#160;:##ticket.closedate## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.requesttype##&lt;/span&gt;&#160;:##ticket.requesttype##&lt;br /&gt;
&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.item.name##&lt;/span&gt;&#160;:
&lt;p&gt;##FOREACHitems##&lt;/p&gt;
&lt;div class="description b"&gt;##IFticket.itemtype## ##ticket.itemtype##&#160;- ##ticket.item.name## ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial## ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## &lt;/div&gt;&lt;br /&gt;
&lt;p&gt;##ENDFOREACHitems##&lt;/p&gt;
##IFticket.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.assigntousers##&lt;/span&gt;&#160;: ##ticket.assigntousers## ##ENDIFticket.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.ticket.status## &lt;/span&gt;&#160;: ##ticket.status##&lt;br /&gt; ##IFticket.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.assigntogroups##&lt;/span&gt;&#160;: ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.urgency##&lt;/span&gt;&#160;: ##ticket.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.impact##&lt;/span&gt;&#160;: ##ticket.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.priority##&lt;/span&gt;&#160;: ##ticket.priority## &lt;br /&gt; ##IFticket.user.email##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.user.email##&lt;/span&gt;&#160;: ##ticket.user.email ##ENDIFticket.user.email##    &lt;br /&gt; ##IFticket.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.content##&lt;/span&gt;&#160;: ##ticket.content##&lt;/p&gt;
&lt;br /&gt;##IFticket.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.ticket.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##ticket.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.ticket.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.ticket.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.description##&lt;br /&gt;##ENDIFticket.storestatus##&lt;/p&gt;
&lt;p&gt;##FOREACHtimelineitems##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;br /&gt;&lt;strong&gt; [##timelineitems.date##]&lt;/strong&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.timelineitems.author## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.author##&lt;/span&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.timelineitems.description## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.description##&lt;/span&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.timelineitems.date## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.date##&lt;/span&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.timelineitems.position## &lt;/span&gt;&lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt; ##timelineitems.position##&lt;/span&gt;&lt;/div&gt;
&lt;div class="description b"&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.timelineitems.type## &lt;/span&gt;&lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt; ##timelineitems.type##&lt;/span&gt;&lt;/div&gt;
&lt;div class="description b"&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.timelineitems.typename## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.typename##&lt;/span&gt;&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtimelineitems##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##&lt;/div&gt;
&lt;div class="description b"&gt;##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##&lt;/div&gt;',
        'new'                      => '<!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    -->
<div>##IFticket.storestatus=5##</div>
<div>##lang.ticket.url## : <a href="##ticket.urlapprove##">##ticket.urlapprove##</a> <strong>&#160;</strong></div>
<div><strong>##lang.ticket.autoclosewarning##</strong></div>
<div><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.type##</strong></span> : ##ticket.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description## ##ENDIFticket.storestatus##</div>
<div>##ELSEticket.storestatus## ##lang.ticket.url## : <a href="##ticket.url##">##ticket.url##</a> ##ENDELSEticket.storestatus##</div>
<p class="description b"><strong>##lang.ticket.description##</strong></p>
<p><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.title##</span>&#160;:##ticket.title## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.authors##</span>&#160;:##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors##    ##ELSEticket.authors##--##ENDELSEticket.authors## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.creationdate##</span>&#160;:##ticket.creationdate## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.closedate##</span>&#160;:##ticket.closedate## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.requesttype##</span>&#160;:##ticket.requesttype##<br />
<br /><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.item.name##</span>&#160;:
<p>##FOREACHitems##</p>
<div class="description b">##IFticket.itemtype## ##ticket.itemtype##&#160;- ##ticket.item.name## ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial## ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## </div><br />
<p>##ENDFOREACHitems##</p>
##IFticket.assigntousers## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.assigntousers##</span>&#160;: ##ticket.assigntousers## ##ENDIFticket.assigntousers##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.ticket.status## </span>&#160;: ##ticket.status##<br /> ##IFticket.assigntogroups## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.assigntogroups##</span>&#160;: ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.urgency##</span>&#160;: ##ticket.urgency##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.impact##</span>&#160;: ##ticket.impact##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.priority##</span>&#160;: ##ticket.priority## <br /> ##IFticket.user.email##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.user.email##</span>&#160;: ##ticket.user.email ##ENDIFticket.user.email##    <br /> ##IFticket.category##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.ticket.category## </span>&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.content##</span>&#160;: ##ticket.content##</p>
<br />##IFticket.storestatus=6##<br /><span style="text-decoration: underline;"><strong><span style="color: #888888;">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.ticket.solution.type##</span></strong></span> : ##ticket.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description##<br />##ENDIFticket.storestatus##</p>
<p>##FOREACHtimelineitems##</p>
<div class="description b"><br /><strong> [##timelineitems.date##]</strong><br /><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.timelineitems.author## </span> <span style="color: #000000; font-weight: bold; text-decoration: underline;">##timelineitems.author##</span><br /><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.timelineitems.description## </span> <span style="color: #000000; font-weight: bold; text-decoration: underline;">##timelineitems.description##</span><br /><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.timelineitems.date## </span> <span style="color: #000000; font-weight: bold; text-decoration: underline;">##timelineitems.date##</span><br /><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.timelineitems.position## </span><span style="color: #000000; font-weight: bold; text-decoration: underline;"> ##timelineitems.position##</span></div>
<div class="description b"><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.timelineitems.type## </span><span style="color: #000000; font-weight: bold; text-decoration: underline;"> ##timelineitems.type##</span></div>
<div class="description b"><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.timelineitems.typename## </span> <span style="color: #000000; font-weight: bold; text-decoration: underline;">##timelineitems.typename##</span></div>
<p>##ENDFOREACHtimelineitems##</p>
<div class="description b">##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##</div>
<div class="description b">##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##</div>',
    ],
    [
        'notificationtemplates_id' => 12,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.contract.entity## : ##contract.entity##&lt;br /&gt;
&lt;br /&gt;##FOREACHcontracts##&lt;br /&gt;##lang.contract.name## :
##contract.name##&lt;br /&gt;
##lang.contract.number## : ##contract.number##&lt;br /&gt;
##lang.contract.time## : ##contract.time##&lt;br /&gt;
##IFcontract.type####lang.contract.type## : ##contract.type##
##ENDIFcontract.type##&lt;br /&gt;
&lt;a href="##contract.url##"&gt;
##contract.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcontracts##&lt;/p&gt;',
        'new'                      => '<p>##lang.contract.entity## : ##contract.entity##<br />
<br />##FOREACHcontracts##<br />##lang.contract.name## :
##contract.name##<br />
##lang.contract.number## : ##contract.number##<br />
##lang.contract.time## : ##contract.time##<br />
##IFcontract.type####lang.contract.type## : ##contract.type##
##ENDIFcontract.type##<br />
<a href="##contract.url##">
##contract.url##</a><br />
##ENDFOREACHcontracts##</p>',
    ],
    [
        'notificationtemplates_id' => 5,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.url##"&gt;
##ticket.url##&lt;/a&gt;&lt;/div&gt;
&lt;div class="description b"&gt;
##lang.ticket.description##&lt;/div&gt;
&lt;p&gt;&lt;span
style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##
&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.authors##&lt;/span&gt;
##IFticket.authors## ##ticket.authors##
##ENDIFticket.authors##
##ELSEticket.authors##--##ENDELSEticket.authors##
&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
&lt;/span&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;
##IFticket.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.content##&lt;/span&gt;&#160;:
##ticket.content##&lt;br /&gt;##IFticket.itemtype##
&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.item.name##&lt;/span&gt;&#160;:
##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##&lt;/p&gt;',
        'new'                      => '<div>##lang.ticket.url## : <a href="##ticket.url##">
##ticket.url##</a></div>
<div class="description b">
##lang.ticket.description##</div>
<p><span
style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">
##lang.ticket.title##</span>&#160;:##ticket.title##
<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">
##lang.ticket.authors##</span>
##IFticket.authors## ##ticket.authors##
##ENDIFticket.authors##
##ELSEticket.authors##--##ENDELSEticket.authors##
<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">
</span><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> </span>
##IFticket.category##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">
##lang.ticket.category## </span>&#160;:##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">
##lang.ticket.content##</span>&#160;:
##ticket.content##<br />##IFticket.itemtype##
<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">
##lang.ticket.item.name##</span>&#160;:
##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##</p>',
    ],
    [
        'notificationtemplates_id' => 15,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.unicity.entity## : ##unicity.entity##&lt;/p&gt;
&lt;p&gt;##lang.unicity.itemtype## : ##unicity.itemtype##&lt;/p&gt;
&lt;p&gt;##lang.unicity.message## : ##unicity.message##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_user## : ##unicity.action_user##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_type## : ##unicity.action_type##&lt;/p&gt;
&lt;p&gt;##lang.unicity.date## : ##unicity.date##&lt;/p&gt;',
        'new'                      => '<p>##lang.unicity.entity## : ##unicity.entity##</p>
<p>##lang.unicity.itemtype## : ##unicity.itemtype##</p>
<p>##lang.unicity.message## : ##unicity.message##</p>
<p>##lang.unicity.action_user## : ##unicity.action_user##</p>
<p>##lang.unicity.action_type## : ##unicity.action_type##</p>
<p>##lang.unicity.date## : ##unicity.date##</p>',
    ],
    [
        'notificationtemplates_id' => 7,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;
&lt;p&gt;##IFvalidation.storestatus=2##&lt;/p&gt;
&lt;div&gt;##validation.submission.title##&lt;/div&gt;
&lt;div&gt;##lang.validation.commentsubmission## : ##validation.commentsubmission##&lt;/div&gt;
&lt;div&gt;##ENDIFvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;&lt;/div&gt;
&lt;div&gt;
&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.urlvalidation##"&gt; ##ticket.urlvalidation## &lt;/a&gt;&lt;/div&gt;
&lt;/div&gt;
&lt;p&gt;##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##
&lt;br /&gt; ##IFvalidation.commentvalidation##&lt;br /&gt; ##lang.validation.commentvalidation## :
&#160; ##validation.commentvalidation##&lt;br /&gt; ##ENDIFvalidation.commentvalidation##
&lt;br /&gt;##ENDFOREACHvalidations##&lt;/p&gt;',
        'new'                      => '<div>##FOREACHvalidations##</div>
<p>##IFvalidation.storestatus=2##</p>
<div>##validation.submission.title##</div>
<div>##lang.validation.commentsubmission## : ##validation.commentsubmission##</div>
<div>##ENDIFvalidation.storestatus##</div>
<div>##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##</div>
<div></div>
<div>
<div>##lang.ticket.url## : <a href="##ticket.urlvalidation##"> ##ticket.urlvalidation## </a></div>
</div>
<p>##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##
<br /> ##IFvalidation.commentvalidation##<br /> ##lang.validation.commentvalidation## :
&#160; ##validation.commentvalidation##<br /> ##ENDIFvalidation.commentvalidation##
<br />##ENDFOREACHvalidations##</p>',
    ],
    [
        'notificationtemplates_id' => 6,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;table class="tab_cadre" border="1" cellspacing="2" cellpadding="3"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.content##&lt;/span&gt;##FOREACHtickets##&lt;/td&gt;
&lt;/tr&gt;
&lt;tr&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;&lt;a href="##ticket.url##"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.content##&lt;/span&gt;##ENDFOREACHtickets##&lt;/td&gt;
&lt;/tr&gt;
&lt;/tbody&gt;
&lt;/table&gt;',
        'new'                      => '<table class="tab_cadre" border="1" cellspacing="2" cellpadding="3">
<tbody>
<tr>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.authors##</span></td>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.title##</span></td>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.priority##</span></td>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.status##</span></td>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.attribution##</span></td>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.creationdate##</span></td>
<td style="text-align: left;" width="auto" bgcolor="#cccccc"><span style="font-size: 11px; text-align: left;">##lang.ticket.content##</span>##FOREACHtickets##</td>
</tr>
<tr>
<td width="auto"><span style="font-size: 11px; text-align: left;">##ticket.authors##</span></td>
<td width="auto"><span style="font-size: 11px; text-align: left;"><a href="##ticket.url##">##ticket.title##</a></span></td>
<td width="auto"><span style="font-size: 11px; text-align: left;">##ticket.priority##</span></td>
<td width="auto"><span style="font-size: 11px; text-align: left;">##ticket.status##</span></td>
<td width="auto"><span style="font-size: 11px; text-align: left;">##IFticket.assigntousers####ticket.assigntousers##<br />##ENDIFticket.assigntousers####IFticket.assigntogroups##<br />##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##<br />##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##</span></td>
<td width="auto"><span style="font-size: 11px; text-align: left;">##ticket.creationdate##</span></td>
<td width="auto"><span style="font-size: 11px; text-align: left;">##ticket.content##</span>##ENDFOREACHtickets##</td>
</tr>
</tbody>
</table>',
    ],
    [
        'notificationtemplates_id' => 9,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;
##lang.consumable.entity## : ##consumable.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##
&lt;br /&gt;##lang.consumable.item## : ##consumable.item##&lt;br /&gt;
&lt;br /&gt;##lang.consumable.reference## : ##consumable.reference##&lt;br /&gt;
##lang.consumable.remaining## : ##consumable.remaining##&lt;br /&gt;
##lang.consumable.stock_target## : ##consumable.stock_target##&lt;br /&gt;
##lang.consumable.to_order## : ##consumable.to_order##&lt;br /&gt;
&lt;a href="##consumable.url##"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;
   ##ENDFOREACHconsumables##&lt;/p&gt;',
        'new'                      => '<p>
##lang.consumable.entity## : ##consumable.entity##
<br /> <br />##FOREACHconsumables##
<br />##lang.consumable.item## : ##consumable.item##<br />
<br />##lang.consumable.reference## : ##consumable.reference##<br />
##lang.consumable.remaining## : ##consumable.remaining##<br />
##lang.consumable.stock_target## : ##consumable.stock_target##<br />
##lang.consumable.to_order## : ##consumable.to_order##<br />
<a href="##consumable.url##"> ##consumable.url##</a><br />
   ##ENDFOREACHconsumables##</p>',
    ],
    [
        'notificationtemplates_id' => 8,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.cartridge.entity## : ##cartridge.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##
&lt;br /&gt;##lang.cartridge.item## :
##cartridge.item##&lt;br /&gt; &lt;br /&gt;
##lang.cartridge.reference## :
##cartridge.reference##&lt;br /&gt;
##lang.cartridge.remaining## :
##cartridge.remaining##&lt;br /&gt;
##lang.cartridge.stock_target## :
##cartridge.stock_target##&lt;br /&gt;
##lang.cartridge.to_order## :
##cartridge.to_order##&lt;br /&gt;
&lt;a href="##cartridge.url##"&gt;
##cartridge.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcartridges##&lt;/p&gt;',
        'new'                      => '<p>##lang.cartridge.entity## : ##cartridge.entity##
<br /> <br />##FOREACHcartridges##
<br />##lang.cartridge.item## :
##cartridge.item##<br /> <br />
##lang.cartridge.reference## :
##cartridge.reference##<br />
##lang.cartridge.remaining## :
##cartridge.remaining##<br />
##lang.cartridge.stock_target## :
##cartridge.stock_target##<br />
##lang.cartridge.to_order## :
##cartridge.to_order##<br />
<a href="##cartridge.url##">
##cartridge.url##</a><br />
##ENDFOREACHcartridges##</p>',
    ],
    [
        'notificationtemplates_id' => 10,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.infocom.entity## : ##infocom.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHinfocoms##
&lt;br /&gt;##lang.infocom.itemtype## : ##infocom.itemtype##&lt;br /&gt;
##lang.infocom.item## : ##infocom.item##&lt;br /&gt; &lt;br /&gt;
##lang.infocom.expirationdate## : ##infocom.expirationdate##
&lt;br /&gt; &lt;a href="##infocom.url##"&gt;
##infocom.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHinfocoms##&lt;/p&gt;',
        'new'                      => '<p>##lang.infocom.entity## : ##infocom.entity##
<br /> <br />##FOREACHinfocoms##
<br />##lang.infocom.itemtype## : ##infocom.itemtype##<br />
##lang.infocom.item## : ##infocom.item##<br /> <br />
##lang.infocom.expirationdate## : ##infocom.expirationdate##
<br /> <a href="##infocom.url##">
##infocom.url##</a><br />
##ENDFOREACHinfocoms##</p>',
    ],
    [
        'notificationtemplates_id' => 11,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;
##lang.license.entity## : ##license.entity##&lt;br /&gt;
##FOREACHlicenses##
&lt;br /&gt;##lang.license.item## : ##license.item##&lt;br /&gt;
##lang.license.serial## : ##license.serial##&lt;br /&gt;
##lang.license.expirationdate## : ##license.expirationdate##
&lt;br /&gt; &lt;a href="##license.url##"&gt; ##license.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHlicenses##&lt;/p&gt;',
        'new'                      => '<p>
##lang.license.entity## : ##license.entity##<br />
##FOREACHlicenses##
<br />##lang.license.item## : ##license.item##<br />
##lang.license.serial## : ##license.serial##<br />
##lang.license.expirationdate## : ##license.expirationdate##
<br /> <a href="##license.url##"> ##license.url##
</a><br /> ##ENDFOREACHlicenses##</p>',
    ],
    [
        'notificationtemplates_id' => 13,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.information##&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.link## &lt;a title="##user.passwordforgeturl##" href="##user.passwordforgeturl##"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;',
        'new'                      => '<p><strong>##user.realname## ##user.firstname##</strong></p>
<p>##lang.passwordforget.information##</p>
<p>##lang.passwordforget.link## <a title="##user.passwordforgeturl##" href="##user.passwordforgeturl##">##user.passwordforgeturl##</a></p>',
    ],
    [
        'notificationtemplates_id' => 14,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.ticket.title## : ##ticket.title##&lt;/p&gt;
&lt;p&gt;##lang.ticket.closedate## : ##ticket.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href="##ticket.urlsatisfaction##"&gt;##ticket.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;',
        'new'                      => '<p>##lang.ticket.title## : ##ticket.title##</p>
<p>##lang.ticket.closedate## : ##ticket.closedate##</p>
<p>##lang.satisfaction.text## <a href="##ticket.urlsatisfaction##">##ticket.urlsatisfaction##</a></p>',
    ],
    [
        'notificationtemplates_id' => 16,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.crontask.warning##&lt;/p&gt;
&lt;p&gt;##FOREACHcrontasks## &lt;br /&gt;&lt;a href="##crontask.url##"&gt;##crontask.name##&lt;/a&gt; : ##crontask.description##&lt;br /&gt; &lt;br /&gt;##ENDFOREACHcrontasks##&lt;/p&gt;',
        'new'                      => '<p>##lang.crontask.warning##</p>
<p>##FOREACHcrontasks## <br /><a href="##crontask.url##">##crontask.name##</a> : ##crontask.description##<br /> <br />##ENDFOREACHcrontasks##</p>',
    ],
    [
        'notificationtemplates_id' => 17,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##IFproblem.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.problem.url## : &lt;a href="##problem.urlapprove##"&gt;##problem.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description## ##ENDIFproblem.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEproblem.storestatus## ##lang.problem.url## : &lt;a href="##problem.url##"&gt;##problem.url##&lt;/a&gt; ##ENDELSEproblem.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.problem.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.title##&lt;/span&gt;&#160;:##problem.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.authors##&lt;/span&gt;&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.creationdate##&lt;/span&gt;&#160;:##problem.creationdate## &lt;br /&gt; ##IFproblem.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.assigntousers##&lt;/span&gt;&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.status## &lt;/span&gt;&#160;: ##problem.status##&lt;br /&gt; ##IFproblem.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.assigntogroups##&lt;/span&gt;&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.urgency##&lt;/span&gt;&#160;: ##problem.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.impact##&lt;/span&gt;&#160;: ##problem.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.priority##&lt;/span&gt; : ##problem.priority## &lt;br /&gt;##IFproblem.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.category## &lt;/span&gt;&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.content##&lt;/span&gt;&#160;: ##problem.content##&lt;/p&gt;
&lt;p&gt;##IFproblem.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.problem.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description##&lt;br /&gt;##ENDIFproblem.storestatus##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.problem.numberoffollowups##&#160;: ##problem.numberoffollowups##&lt;/div&gt;
&lt;p&gt;##FOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;br /&gt; &lt;strong&gt; [##followup.date##] &lt;em&gt;##lang.followup.isprivate## : ##followup.isprivate## &lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.author## &lt;/span&gt; ##followup.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.description## &lt;/span&gt; ##followup.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.date## &lt;/span&gt; ##followup.date##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.requesttype## &lt;/span&gt; ##followup.requesttype##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##&lt;/div&gt;
&lt;p&gt;##FOREACHtickets##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##ticket.date##] &lt;em&gt;##lang.problem.title## : &lt;a href="##ticket.url##"&gt;##ticket.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.content## &lt;/span&gt; ##ticket.content##
&lt;p&gt;##ENDFOREACHtickets##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
        'new'                      => '<p>##IFproblem.storestatus=5##</p>
<div>##lang.problem.url## : <a href="##problem.urlapprove##">##problem.urlapprove##</a></div>
<div><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.problem.solvedate##</span></strong></span> : ##problem.solvedate##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.problem.solution.type##</strong></span> : ##problem.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.problem.solution.description##</strong></span> : ##problem.solution.description## ##ENDIFproblem.storestatus##</div>
<div>##ELSEproblem.storestatus## ##lang.problem.url## : <a href="##problem.url##">##problem.url##</a> ##ENDELSEproblem.storestatus##</div>
<p class="description b"><strong>##lang.problem.description##</strong></p>
<p><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.title##</span>&#160;:##problem.title## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.authors##</span>&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.creationdate##</span>&#160;:##problem.creationdate## <br /> ##IFproblem.assigntousers## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.assigntousers##</span>&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.problem.status## </span>&#160;: ##problem.status##<br /> ##IFproblem.assigntogroups## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.assigntogroups##</span>&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.urgency##</span>&#160;: ##problem.urgency##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.impact##</span>&#160;: ##problem.impact##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.priority##</span> : ##problem.priority## <br />##IFproblem.category##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.problem.category## </span>&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.problem.content##</span>&#160;: ##problem.content##</p>
<p>##IFproblem.storestatus=6##<br /><span style="text-decoration: underline;"><strong><span style="color: #888888;">##lang.problem.solvedate##</span></strong></span> : ##problem.solvedate##<br /><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.problem.solution.type##</span></strong></span> : ##problem.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.problem.solution.description##</strong></span> : ##problem.solution.description##<br />##ENDIFproblem.storestatus##</p>
<div class="description b">##lang.problem.numberoffollowups##&#160;: ##problem.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class="description b"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.author## </span> ##followup.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.description## </span> ##followup.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.date## </span> ##followup.date##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
<div class="description b">##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##</div>
<p>##FOREACHtickets##</p>
<div><strong> [##ticket.date##] <em>##lang.problem.title## : <a href="##ticket.url##">##ticket.title## </a></em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> </span><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.problem.content## </span> ##ticket.content##
<p>##ENDFOREACHtickets##</p>
<div class="description b">##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##</div>
<p>##FOREACHtasks##</p>
<div class="description b"><strong>[##task.date##] </strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.author##</span> ##task.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.description##</span> ##task.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.time##</span> ##task.time##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.category##</span> ##task.category##</div>
<p>##ENDFOREACHtasks##</p>
</div>',
    ],
    [
        'notificationtemplates_id' => 18,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##recall.action##: &lt;a href="##recall.item.url##"&gt;##recall.item.name##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;##recall.item.content##&lt;/p&gt;
&lt;p&gt;##lang.recall.planning.begin##: ##recall.planning.begin##&lt;br /&gt;##lang.recall.planning.end##: ##recall.planning.end##&lt;br /&gt;##lang.recall.planning.state##: ##recall.planning.state##&lt;br /&gt;##lang.recall.item.private##: ##recall.item.private##&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;
&lt;p&gt;&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;',
        'new'                      => '<p>##recall.action##: <a href="##recall.item.url##">##recall.item.name##</a></p>
<p>##recall.item.content##</p>
<p>##lang.recall.planning.begin##: ##recall.planning.begin##<br />##lang.recall.planning.end##: ##recall.planning.end##<br />##lang.recall.planning.state##: ##recall.planning.state##<br />##lang.recall.item.private##: ##recall.item.private##<br /><br /></p>
<p><br /><br /></p>',
    ],
    [
        'notificationtemplates_id' => 19,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##IFchange.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.change.url## : &lt;a href="##change.urlapprove##"&gt;##change.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description## ##ENDIFchange.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEchange.storestatus## ##lang.change.url## : &lt;a href="##change.url##"&gt;##change.url##&lt;/a&gt; ##ENDELSEchange.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.change.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.title##&lt;/span&gt;&#160;:##change.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.authors##&lt;/span&gt;&#160;:##IFchange.authors## ##change.authors## ##ENDIFchange.authors##    ##ELSEchange.authors##--##ENDELSEchange.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.creationdate##&lt;/span&gt;&#160;:##change.creationdate## &lt;br /&gt; ##IFchange.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.assigntousers##&lt;/span&gt;&#160;: ##change.assigntousers## ##ENDIFchange.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.status## &lt;/span&gt;&#160;: ##change.status##&lt;br /&gt; ##IFchange.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.assigntogroups##&lt;/span&gt;&#160;: ##change.assigntogroups## ##ENDIFchange.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.urgency##&lt;/span&gt;&#160;: ##change.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.impact##&lt;/span&gt;&#160;: ##change.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.priority##&lt;/span&gt; : ##change.priority## &lt;br /&gt;##IFchange.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.category## &lt;/span&gt;&#160;:##change.category##  ##ENDIFchange.category## ##ELSEchange.category##  ##lang.change.nocategoryassigned## ##ENDELSEchange.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.content##&lt;/span&gt;&#160;: ##change.content##&lt;/p&gt;
&lt;p&gt;##IFchange.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.change.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description##&lt;br /&gt;##ENDIFchange.storestatus##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.change.numberoffollowups##&#160;: ##change.numberoffollowups##&lt;/div&gt;
&lt;p&gt;##FOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;br /&gt; &lt;strong&gt; [##followup.date##] &lt;em&gt;##lang.followup.isprivate## : ##followup.isprivate## &lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.author## &lt;/span&gt; ##followup.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.description## &lt;/span&gt; ##followup.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.date## &lt;/span&gt; ##followup.date##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.requesttype## &lt;/span&gt; ##followup.requesttype##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.change.numberofproblems##&#160;: ##change.numberofproblems##&lt;/div&gt;
&lt;p&gt;##FOREACHproblems##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##problem.date##] &lt;em&gt;##lang.change.title## : &lt;a href="##problem.url##"&gt;##problem.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.content## &lt;/span&gt; ##problem.content##
&lt;p&gt;##ENDFOREACHproblems##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.change.numberoftasks##&#160;: ##change.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
        'new'                      => '<p>##IFchange.storestatus=5##</p>
<div>##lang.change.url## : <a href="##change.urlapprove##">##change.urlapprove##</a></div>
<div><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.change.solvedate##</span></strong></span> : ##change.solvedate##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.change.solution.type##</strong></span> : ##change.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.change.solution.description##</strong></span> : ##change.solution.description## ##ENDIFchange.storestatus##</div>
<div>##ELSEchange.storestatus## ##lang.change.url## : <a href="##change.url##">##change.url##</a> ##ENDELSEchange.storestatus##</div>
<p class="description b"><strong>##lang.change.description##</strong></p>
<p><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.title##</span>&#160;:##change.title## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.authors##</span>&#160;:##IFchange.authors## ##change.authors## ##ENDIFchange.authors##    ##ELSEchange.authors##--##ENDELSEchange.authors## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.creationdate##</span>&#160;:##change.creationdate## <br /> ##IFchange.assigntousers## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.assigntousers##</span>&#160;: ##change.assigntousers## ##ENDIFchange.assigntousers##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.change.status## </span>&#160;: ##change.status##<br /> ##IFchange.assigntogroups## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.assigntogroups##</span>&#160;: ##change.assigntogroups## ##ENDIFchange.assigntogroups##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.urgency##</span>&#160;: ##change.urgency##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.impact##</span>&#160;: ##change.impact##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.priority##</span> : ##change.priority## <br />##IFchange.category##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.change.category## </span>&#160;:##change.category##  ##ENDIFchange.category## ##ELSEchange.category##  ##lang.change.nocategoryassigned## ##ENDELSEchange.category##    <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.change.content##</span>&#160;: ##change.content##</p>
<p>##IFchange.storestatus=6##<br /><span style="text-decoration: underline;"><strong><span style="color: #888888;">##lang.change.solvedate##</span></strong></span> : ##change.solvedate##<br /><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.change.solution.type##</span></strong></span> : ##change.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.change.solution.description##</strong></span> : ##change.solution.description##<br />##ENDIFchange.storestatus##</p>
<div class="description b">##lang.change.numberoffollowups##&#160;: ##change.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class="description b"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.author## </span> ##followup.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.description## </span> ##followup.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.date## </span> ##followup.date##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
<div class="description b">##lang.change.numberofproblems##&#160;: ##change.numberofproblems##</div>
<p>##FOREACHproblems##</p>
<div><strong> [##problem.date##] <em>##lang.change.title## : <a href="##problem.url##">##problem.title## </a></em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> </span><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.change.content## </span> ##problem.content##
<p>##ENDFOREACHproblems##</p>
<div class="description b">##lang.change.numberoftasks##&#160;: ##change.numberoftasks##</div>
<p>##FOREACHtasks##</p>
<div class="description b"><strong>[##task.date##] </strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.author##</span> ##task.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.description##</span> ##task.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.time##</span> ##task.time##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.category##</span> ##task.category##</div>
<p>##ENDFOREACHtasks##</p>
</div>',
    ],
    [
        'notificationtemplates_id' => 20,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##FOREACHmailcollectors##&lt;br /&gt;##lang.mailcollector.name## : ##mailcollector.name##&lt;br /&gt; ##lang.mailcollector.errors## : ##mailcollector.errors##&lt;br /&gt;&lt;a href="##mailcollector.url##"&gt;##mailcollector.url##&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHmailcollectors##&lt;/p&gt;
&lt;p&gt;&lt;/p&gt;',
        'new'                      => '<p>##FOREACHmailcollectors##<br />##lang.mailcollector.name## : ##mailcollector.name##<br /> ##lang.mailcollector.errors## : ##mailcollector.errors##<br /><a href="##mailcollector.url##">##mailcollector.url##</a><br /> ##ENDFOREACHmailcollectors##</p>
<p></p>',
    ],
    [
        'notificationtemplates_id' => 21,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.project.url## : &lt;a href="##project.url##"&gt;##project.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.project.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.project.name## : ##project.name##&lt;br /&gt;##lang.project.code## : ##project.code##&lt;br /&gt; ##lang.project.manager## : ##project.manager##&lt;br /&gt;##lang.project.managergroup## : ##project.managergroup##&lt;br /&gt; ##lang.project.creationdate## : ##project.creationdate##&lt;br /&gt;##lang.project.priority## : ##project.priority## &lt;br /&gt;##lang.project.state## : ##project.state##&lt;br /&gt;##lang.project.type## : ##project.type##&lt;br /&gt;##lang.project.description## : ##project.description##&lt;/p&gt;
&lt;p&gt;##lang.project.numberoftasks## : ##project.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt; ##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
        'new'                      => '<p>##lang.project.url## : <a href="##project.url##">##project.url##</a></p>
<p><strong>##lang.project.description##</strong></p>
<p>##lang.project.name## : ##project.name##<br />##lang.project.code## : ##project.code##<br /> ##lang.project.manager## : ##project.manager##<br />##lang.project.managergroup## : ##project.managergroup##<br /> ##lang.project.creationdate## : ##project.creationdate##<br />##lang.project.priority## : ##project.priority## <br />##lang.project.state## : ##project.state##<br />##lang.project.type## : ##project.type##<br />##lang.project.description## : ##project.description##</p>
<p>##lang.project.numberoftasks## : ##project.numberoftasks##</p>
<div>
<p>##FOREACHtasks##</p>
<div><strong>[##task.creationdate##] </strong><br /> ##lang.task.name## : ##task.name##<br />##lang.task.state## : ##task.state##<br />##lang.task.type## : ##task.type##<br />##lang.task.percent## : ##task.percent##<br />##lang.task.description## : ##task.description##</div>
<p>##ENDFOREACHtasks##</p>
</div>',
    ],
    [
        'notificationtemplates_id' => 22,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.projecttask.url## : &lt;a href="##projecttask.url##"&gt;##projecttask.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.projecttask.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.projecttask.name## : ##projecttask.name##&lt;br /&gt;##lang.projecttask.project## : &lt;a href="##projecttask.projecturl##"&gt;##projecttask.project##&lt;/a&gt;&lt;br /&gt;##lang.projecttask.creationdate## : ##projecttask.creationdate##&lt;br /&gt;##lang.projecttask.state## : ##projecttask.state##&lt;br /&gt;##lang.projecttask.type## : ##projecttask.type##&lt;br /&gt;##lang.projecttask.description## : ##projecttask.description##&lt;/p&gt;
&lt;p&gt;##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt;##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
        'new'                      => '<p>##lang.projecttask.url## : <a href="##projecttask.url##">##projecttask.url##</a></p>
<p><strong>##lang.projecttask.description##</strong></p>
<p>##lang.projecttask.name## : ##projecttask.name##<br />##lang.projecttask.project## : <a href="##projecttask.projecturl##">##projecttask.project##</a><br />##lang.projecttask.creationdate## : ##projecttask.creationdate##<br />##lang.projecttask.state## : ##projecttask.state##<br />##lang.projecttask.type## : ##projecttask.type##<br />##lang.projecttask.description## : ##projecttask.description##</p>
<p>##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##</p>
<div>
<p>##FOREACHtasks##</p>
<div><strong>[##task.creationdate##] </strong><br />##lang.task.name## : ##task.name##<br />##lang.task.state## : ##task.state##<br />##lang.task.type## : ##task.type##<br />##lang.task.percent## : ##task.percent##<br />##lang.task.description## : ##task.description##</div>
<p>##ENDFOREACHtasks##</p>
</div>',
    ],
    [
        'notificationtemplates_id' => 23,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan="2"&gt;&lt;a href="##objectlock.url##"&gt;##objectlock.type## ###objectlock.id## - ##objectlock.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.url##&lt;/td&gt;
      &lt;td&gt;##objectlock.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.date_mod##&lt;/td&gt;
      &lt;td&gt;##objectlock.date_mod##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style="font-size: small;"&gt;Hello ##objectlock.lockedby.firstname##,&lt;br /&gt;Could go to this item and unlock it for me?&lt;br /&gt;Thank you,&lt;br /&gt;Regards,&lt;br /&gt;##objectlock.requester.firstname## ##objectlock.requester.lastname##&lt;/span&gt;&lt;/p&gt;',
        'new'                      => '<table>
      <tbody>
      <tr><th colspan="2"><a href="##objectlock.url##">##objectlock.type## ###objectlock.id## - ##objectlock.name##</a></th></tr>
      <tr>
      <td>##lang.objectlock.url##</td>
      <td>##objectlock.url##</td>
      </tr>
      <tr>
      <td>##lang.objectlock.date_mod##</td>
      <td>##objectlock.date_mod##</td>
      </tr>
      </tbody>
      </table>
      <p><span style="font-size: small;">Hello ##objectlock.lockedby.firstname##,<br />Could go to this item and unlock it for me?<br />Thank you,<br />Regards,<br />##objectlock.requester.firstname## ##objectlock.requester.lastname##</span></p>',
    ],
    [
        'notificationtemplates_id' => 24,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan="2"&gt;&lt;a href="##savedsearch.url##"&gt;##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;&lt;td colspan="2"&gt;&lt;a href="##savedsearch.url##"&gt;##savedsearch.message##&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.savedsearch.url##&lt;/td&gt;
      &lt;td&gt;##savedsearch.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style="font-size: small;"&gt;Hello &lt;br /&gt;Regards,&lt;/span&gt;&lt;/p&gt;',
        'new'                      => '<table>
      <tbody>
      <tr><th colspan="2"><a href="##savedsearch.url##">##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##</a></th></tr>
      <tr><td colspan="2"><a href="##savedsearch.url##">##savedsearch.message##</a></td></tr>
      <tr>
      <td>##lang.savedsearch.url##</td>
      <td>##savedsearch.url##</td>
      </tr>
      </tbody>
      </table>
      <p><span style="font-size: small;">Hello <br />Regards,</span></p>',
    ],
    [
        'notificationtemplates_id' => 25,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;
##lang.certificate.entity## : ##certificate.entity##&lt;br /&gt;
&lt;br /&gt;##lang.certificate.name## : ##certificate.name##&lt;br /&gt;
##lang.certificate.serial## : ##certificate.serial##&lt;br /&gt;
##lang.certificate.expirationdate## : ##certificate.expirationdate##
&lt;br /&gt; &lt;a href="##certificate.url##"&gt; ##certificate.url##
&lt;/a&gt;&lt;br /&gt;
&lt;/p&gt;',
        'new'                      => '<p>
##lang.certificate.entity## : ##certificate.entity##<br />
<br />##lang.certificate.name## : ##certificate.name##<br />
##lang.certificate.serial## : ##certificate.serial##<br />
##lang.certificate.expirationdate## : ##certificate.expirationdate##
<br /> <a href="##certificate.url##"> ##certificate.url##
</a><br />
</p>',
    ],
    [
        'notificationtemplates_id' => 26,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.domain.entity## :##domain.entity##&lt;br /&gt; &lt;br /&gt;
                        ##lang.domain.name##  : ##domain.name## - ##lang.domain.dateexpiration## :  ##domain.dateexpiration##&lt;br /&gt;
                        &lt;/p&gt;',
        'new'                      => '<p>##lang.domain.entity## :##domain.entity##<br /> <br />
                        ##lang.domain.name##  : ##domain.name## - ##lang.domain.dateexpiration## :  ##domain.dateexpiration##<br />
                        </p>',
    ],
    [
        'notificationtemplates_id' => 27,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;

##IFuser.password.has_expired=1##
&lt;p&gt;##lang.password.has_expired.information##&lt;/p&gt;
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
&lt;p&gt;##lang.password.expires_soon.information##&lt;/p&gt;
##ENDELSEuser.password.has_expired##
&lt;p&gt;##lang.user.password.expiration.date##: ##user.password.expiration.date##&lt;/p&gt;
##IFuser.account.lock.date##
&lt;p&gt;##lang.user.account.lock.date##: ##user.account.lock.date##&lt;/p&gt;
##ENDIFuser.account.lock.date##

&lt;p&gt;##lang.password.update.link## &lt;a href="##user.password.update.url##"&gt;##user.password.update.url##&lt;/a&gt;&lt;/p&gt;',
        'new'                      => '<p><strong>##user.realname## ##user.firstname##</strong></p>

##IFuser.password.has_expired=1##
<p>##lang.password.has_expired.information##</p>
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
<p>##lang.password.expires_soon.information##</p>
##ENDELSEuser.password.has_expired##
<p>##lang.user.password.expiration.date##: ##user.password.expiration.date##</p>
##IFuser.account.lock.date##
<p>##lang.user.account.lock.date##: ##user.account.lock.date##</p>
##ENDIFuser.account.lock.date##

<p>##lang.password.update.link## <a href="##user.password.update.url##">##user.password.update.url##</a></p>',
    ],
    [
        'notificationtemplates_id' => 28,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.plugins_updates_available##&lt;/p&gt;
&lt;ul&gt;##FOREACHplugins##
&lt;li&gt;##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##&lt;/li&gt;
##ENDFOREACHplugins##&lt;/ul&gt;
&lt;p&gt;##lang.marketplace.url## : &lt;a title="##lang.marketplace.url##" href="##marketplace.url##" target="_blank" rel="noopener"&gt;##marketplace.url##&lt;/a&gt;&lt;/p&gt;',
        'new'                      => '<p>##lang.plugins_updates_available##</p>
<ul>##FOREACHplugins##
<li>##plugin.name## :##plugin.old_version## -> ##plugin.version##</li>
##ENDFOREACHplugins##</ul>
<p>##lang.marketplace.url## : <a title="##lang.marketplace.url##" href="##marketplace.url##" target="_blank" rel="noopener">##marketplace.url##</a></p>',
    ],
    [
        'notificationtemplates_id' => 28,
        'language'                 => '',
        'field'                    => 'content_text',
        'old'                      => '##lang.plugins_updates_available##

##FOREACHplugins##
##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##
##ENDFOREACHplugins##

##lang.marketplace.url## : ##marketplace.url##',
        'new'                      => '##lang.plugins_updates_available##

##FOREACHplugins##
##plugin.name## :##plugin.old_version## -> ##plugin.version##
##ENDFOREACHplugins##

##lang.marketplace.url## : ##marketplace.url##',
    ],
    [
        'notificationtemplates_id' => 29,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.passwordinit.information##&lt;/p&gt;
&lt;p&gt;##lang.passwordinit.link## &lt;a title="##user.passwordiniturl##" href="##user.passwordiniturl##"&gt;##user.passwordiniturl##&lt;/a&gt;&lt;/p&gt;',
        'new'                      => '<p><strong>##user.realname## ##user.firstname##</strong></p>
<p>##lang.passwordinit.information##</p>
<p>##lang.passwordinit.link## <a title="##user.passwordiniturl##" href="##user.passwordiniturl##">##user.passwordiniturl##</a></p>',
    ],
    [
        'notificationtemplates_id' => 30,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.change.title## : ##change.title##&lt;/p&gt;
&lt;p&gt;##lang.change.closedate## : ##change.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href="##change.urlsatisfaction##"&gt;##change.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;',
        'new'                      => '<p>##lang.change.title## : ##change.title##</p>
<p>##lang.change.closedate## : ##change.closedate##</p>
<p>##lang.satisfaction.text## <a href="##change.urlsatisfaction##">##change.urlsatisfaction##</a></p>',
    ],
    [
        'notificationtemplates_id' => 31,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.ticket.title##: ##ticket.title##&lt;/p&gt;
                    &lt;p&gt;##lang.ticket.reminder.bumpcounter##: ##ticket.reminder.bumpcounter##&lt;/a&gt;&lt;br /&gt;
                    ##lang.ticket.reminder.bumpremaining##: ##ticket.reminder.bumpremaining##&lt;/a&gt;&lt;br /&gt;
                    ##lang.ticket.reminder.bumptotal##: ##ticket.reminder.bumptotal##&lt;/a&gt;&lt;br /&gt;
                    ##lang.ticket.reminder.deadline##: ##ticket.reminder.deadline##&lt;/p&gt;
                    &lt;p&gt;##lang.ticket.reminder.text##: ##ticket.reminder.text##&lt;/p&gt;',
        'new'                      => '<p>##lang.ticket.title##: ##ticket.title##</p>
                    <p>##lang.ticket.reminder.bumpcounter##: ##ticket.reminder.bumpcounter##</a><br />
                    ##lang.ticket.reminder.bumpremaining##: ##ticket.reminder.bumpremaining##</a><br />
                    ##lang.ticket.reminder.bumptotal##: ##ticket.reminder.bumptotal##</a><br />
                    ##lang.ticket.reminder.deadline##: ##ticket.reminder.deadline##</p>
                    <p>##lang.ticket.reminder.text##: ##ticket.reminder.text##</p>',
    ],
    [
        'notificationtemplates_id' => 32,
        'language'                 => '',
        'field'                    => 'content_html',
        'old'                      => '&lt;p&gt;##lang.knowbaseitem.subject## : ##knowbaseitem.subject##
&lt;br&gt;##lang.knowbaseitem.categories## : ##knowbaseitem.categories##
&lt;br&gt;##lang.knowbaseitem.is_faq## ##knowbaseitem.is_faq##
&lt;br&gt;##lang.knowbaseitem.begin_date## : ##knowbaseitem.begin_date##
&lt;br&gt;##lang.knowbaseitem.end_date## : ##knowbaseitem.end_date##
&lt;br&gt;##lang.knowbaseitem.numberofdocuments## : ##knowbaseitem.numberofdocuments##&lt;/p&gt;
##FOREACHdocuments## &lt;p&gt;##lang.document.downloadurl## : ##document.downloadurl##&lt;/p&gt;
&lt;p&gt;##lang.document.filename## : ##document.filename##&lt;/p&gt;
&lt;p&gt;##lang.document.heading## : ##document.heading##&lt;/p&gt;
&lt;p&gt;##lang.document.id## : ##document.id##&lt;/p&gt;
&lt;p&gt;##lang.document.name## : ##document.name##&lt;/p&gt;
&lt;p&gt;##lang.document.url## : ##document.url##&lt;/p&gt;
&lt;p&gt;##lang.document.weblink## : ##document.weblink##&lt;/p&gt; ##ENDFOREACHdocuments##&lt;/p&gt;
##FOREACHtargets## &lt;p&gt;##lang.target.itemtype## : ##target.type##&lt;/p&gt;
&lt;p&gt;##lang.target.name## : ##target.name##&lt;/p&gt;
&lt;p&gt;##lang.target.url## : ##target.url##&lt;/p&gt; ##ENDFOREACHtargets##',
        'new'                      => '<p>##lang.knowbaseitem.subject## : ##knowbaseitem.subject##
<br>##lang.knowbaseitem.categories## : ##knowbaseitem.categories##
<br>##lang.knowbaseitem.is_faq## ##knowbaseitem.is_faq##
<br>##lang.knowbaseitem.begin_date## : ##knowbaseitem.begin_date##
<br>##lang.knowbaseitem.end_date## : ##knowbaseitem.end_date##
<br>##lang.knowbaseitem.numberofdocuments## : ##knowbaseitem.numberofdocuments##</p>
##FOREACHdocuments## <p>##lang.document.downloadurl## : ##document.downloadurl##</p>
<p>##lang.document.filename## : ##document.filename##</p>
<p>##lang.document.heading## : ##document.heading##</p>
<p>##lang.document.id## : ##document.id##</p>
<p>##lang.document.name## : ##document.name##</p>
<p>##lang.document.url## : ##document.url##</p>
<p>##lang.document.weblink## : ##document.weblink##</p> ##ENDFOREACHdocuments##</p>
##FOREACHtargets## <p>##lang.target.itemtype## : ##target.type##</p>
<p>##lang.target.name## : ##target.name##</p>
<p>##lang.target.url## : ##target.url##</p> ##ENDFOREACHtargets##',
    ],
];
