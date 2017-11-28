    <h2>CMS Homepage</h2>   

    <h5>Recently Edited Content Elements:</h5>
    <div id="recentlyeditedcontent">
    <?php
    $can_edit = (Core::auth('users') || Core::auth('developer') ) ? true : false;
    $recent_contents = ORM::for_table(_table_content)
                    ->raw_query("SELECT *
                                    FROM "._table_content." AS topTable
                                    WHERE revision_date != '0000-00-00 00:00:00' 
                                    AND content != '' 
                                    AND revision_date = (SELECT revision_date
                                                        FROM "._table_content." 
                                                        WHERE page_id = topTable.page_id 
                                                        AND type_id = topTable.type_id 
                                                        ORDER BY revision_date DESC 
                                                        LIMIT 1) 
                                    ORDER BY revision_date DESC
                                    LIMIT 10")
                    ->find_many();    
    $getcontentTypes = ORM::for_table(_table_content_types)->find_many();
    foreach($getcontentTypes as $getType)
    {
        $contentTypes[$getType->id] = $getType->name;
    }

    $tools = new Core_Model_Tools;
       
    echo "<table width=\"100%\"><thead><th>&nbsp</th><th>Page Label</th><th>Type</th><th>Status</th><th>&nbsp;</th></thead><tbody>";                    
    foreach($recent_contents as $recent_content):
        $recentPage = ORM::for_table(_table_pages)->find_one($recent_content->page_id);
        echo '<td><span class="tooltip" title="'. date("F j, Y h:ia",strtotime($recent_content->revision_date)) .'">'.$tools->time_ago($recent_content->revision_date) .'</span></td>';
        echo "<td>".$recentPage->label."</td>";
        echo "<td>".$contentTypes[$recent_content->type_id]."</td>";
        echo "<td>"; 
            if($recent_content->live == 1){
                echo '<span class="recent_content_live">Live</span>';
            }else if ($recent_content->publish_date != "0000-00-00 00:00:00"){
                echo '<span class="recent_content_previsoulyLive">Previously Published</span>';
            }else{
                echo '<span class="recent_content_draft">Draft</span>';
            }
        echo "</td>";
        echo "<td>";
            if($can_edit){
                echo '<a href="'. _app_path .'admin/edit/'.$recent_content->id.'">Edit</a>';
            }
        echo "</td>";
        echo "</tr>\n";
        
    endforeach;
    echo "</tbody></table>";
?>
    </div>


<?php if(Core::auth('content') || Core::auth('developer')) : ?>
<style type="text/css">
    /* have left hand "Content Nav" drawer already open on page load */
    #editnav { margin-left: 0; }
    #content { margin-left: 300; }
</style>
<?php endif; ?>