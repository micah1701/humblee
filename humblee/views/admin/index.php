<h1 class="title">Welcome, <?php echo $user->name ?></h2>

<?php 
if(!Core::auth(array('content','developer')))
{
    return;
}
?>

<div class="columns">
    <div id="editnav" class="column is-one-half">
        <p class="is-size-5">Edit Content by Page</p>
        <aside id="contentMenu" class="menu">&nbsp; loading...</aside>
    </div>
  
    <div  class="column">       
        <p class="is-size-5">Recently Edited Content Elements:</p>
        <aside id="recentlyeditedcontent">
        <?php
        echo "<table class=\"table is-striped is-hoverable\" width=\"100%\"><thead><th>&nbsp</th><th>Page Label</th><th>Type</th><th>Status</th><th>&nbsp;</th></thead><tbody>";                    
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
                if(Core::auth(array('content','developer')))
                {
            ?>
                <a href="<?php echo  _app_path .'admin/edit/'.$recent_content->id ?>" class="button is-info">
                    <span class="icon is-small"><i class="fas fa-edit is-info"></i></span>
                    <span class="is-pulled-right">Edit</span>
                </a>
            <?php
                }
            echo "</td>";
            echo "</tr>\n";
            
        endforeach;
        echo "</tbody></table>";
    ?>
        </aside>
    </div>
</div>