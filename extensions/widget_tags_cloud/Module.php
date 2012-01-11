<?php

/*******************************************************************************
    ShareMyPics, a free twitpic clone
    Copyright (C) 2012 Jimmy Rudolf

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

/**
 * A widget that shows a tags cloud
 */
class widget_tags_cloud {
    private static $smp = null;
    private static $conf = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/widget_tags_cloud/settings.php";
        self::$conf = $conf;
        
        /* Registers with the "timeline widget" event name" */
        self::$smp->api->registerExtension("timeline widget", array("name" => "widget_tags_cloud", "function" => "show_cloud"));
    }    
    
    public function show_cloud() {
        $tags = self::$smp->database->selectList("Keywords AS k, FilesKeywords AS fk", "k.*, fk.*, COUNT(fk.keyword_id) AS Occurrences", "k.keyword_id=fk.keyword_id", "", self::$conf["widget_tags_cloud"]["Count"], "fk.keyword_id");
                
        require_once "HTML/TagCloud.php";
        
        $cloud = new HTML_TagCloud();
        
        foreach($tags as $tag) {
            $cloud->addElement($tag->keyword, self::$smp->getWebRoot() . "/timeline.php?q=" . $tag->keyword, $tag->occurrences);
        }
        
        $feedback = new Feedback(
            "widget_tags_cloud", 
            "show_cloud", 
            FEEDBACK_WIDGET,
            false,
            Array(
                "title"     => _("Popular tags"),   // Will be displayed on top of the widget
                "content"   => $cloud->buildALL()
            )
        );
        
        self::$smp->api->sendFeedback("timeline widget", $feedback);
    }
}

?>