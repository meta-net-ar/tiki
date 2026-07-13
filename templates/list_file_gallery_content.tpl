{if empty($sort_arg)}
    {$sort_arg='sort_mode'}
{/if}
<div class="table-responsive styling-element">
    <table class="table">
        <tr>
            {if !empty($files) and $gal_info.show_checked ne 'n' and ($tiki_p_admin_file_galleries eq 'y' or $tiki_p_upload_files eq 'y')}
                {if isset($nbCols)}
                    {$nbCols=$nbCols+1}
                {else}
                    {$nbCols=1}
                {/if}
                <td class="checkbox-cell"> {* th changed to td to avoid ARIA empty header error  *}
                    {select_all checkbox_names='file[],subgal[]'}
                </td>
            {/if}

            {if ( $prefs.use_context_menu_icon eq 'y' or $prefs.use_context_menu_text eq 'y' )
                and (!isset($gal_info.show_action) or $gal_info.show_action eq 'y')}
                {if isset($nbCols)}
                    {$nbCols=$nbCols+1}
                {else}
                    {$nbCols = 1}
                {/if}
                <td style="width: 1em">&nbsp; {* th changed to td to avoid ARIA empty header error  *}

                </td>
            {/if}

            {if isset($gal_info.show_parentName) && $gal_info.show_parentName eq 'y'}
                <th>
                    {self_link _sort_arg=$sort_arg _sort_field='parentName'}{tr}Gallery{/tr}{/self_link}
                </th>
            {/if}
            {if !empty($show_thumb) and $show_thumb eq 'y'}
                <th>
                </th>
            {/if}

            {foreach from=$fgal_listing_conf item=item key=propname}
                {if isset($item.key)}
                    {$key_name=$item.key}
                {else}
                    {$key_name="show_$propname"}
                {/if}

                {if isset($gal_info.$key_name) and $gal_info.$key_name eq 'o'}
                    {$show_infos='y'}
                    {if $sort_mode eq $propname|cat:'_asc' or $sort_mode eq $propname|cat:'_desc'}
                        {$other_columns_selected=$propname}
                    {else}
                        {capture assign=other_columns}
                            {if isset($other_columns)}
                                {$other_columns}
                            {/if}
                            {if !empty($item.description)}
                                {self_link sort_mode=$propname|cat:'_asc' _title=$item.description _class='tips'}{$fgal_listing_conf.$propname.name}{/self_link}<br>
                            {else}
                                {self_link sort_mode=$propname|cat:'_asc'}{$fgal_listing_conf.$propname.name}{/self_link}<br>
                            {/if}
                        {/capture}
                    {/if}
                {/if}

                {if isset($gal_info.$key_name) and ( $gal_info.$key_name eq 'y' or $gal_info.$key_name eq 'i'
                    or $gal_info.$key_name eq 'a' or $propname eq 'name' )}
                    {$propval=$item.name}
                    {$link_title=$item.description|default:''}
                    {$td_args=''}

                    {if $gal_info.$key_name eq 'i' or $propname eq 'type' or ( $propname eq 'lockedby'
                        and $gal_info.$key_name eq 'a')}
                        {if isset($item.icon)}
                            {$propicon=$item.icon}
                        {else}
                            {$propval=$item.name[0]}
                            {/if}
                        {$link_title=$item.name}
                        {$td_args=$td_args|cat:' style="width: 1em;text-align:center"'}
                    {/if}

                    {if $propname eq 'name' and ( $gal_info.show_name eq 'a' or $gal_info.show_name eq 'f' )}
                        {if isset($nbCols)}
                            {$nbCols=$nbCols+1}
                        {else}
                            {$nbCols=1}
                        {/if}
                        <th{$td_args}>
                            {self_link _sort_arg=$sort_arg _sort_field='filename'}
                                {tr}Filename{/tr}
                            {/self_link}
                        </th>
                    {/if}

                    {if !(empty($galleryId) and $propname eq 'lockedby') and ($propname neq 'name'
                        or ( $gal_info.show_name eq 'a' or $gal_info.show_name eq 'n' )) and ($propname neq 'description'
                        or $gal_info.show_name neq 'n'
                        or $gal_info.show_description eq 'y' or $gal_info.show_description eq 'a')}
                        {if isset($nbCols)}
                            {$nbCols=$nbCols+1}
                        {else}
                            {$nbCols=1}
                        {/if}
                        <th{$td_args}>
                            {self_link _sort_arg=$sort_arg _sort_field=$propname _title=":$link_title" _class='tips'}
                                {if !empty($propicon)}
                                    {icon name=$propicon alt=$link_title}
                                {else}
                                    {$propval}
                                {/if}
                            {/self_link}
                        </th>
                    {/if}
                {/if}
            {/foreach}

            {if !empty($other_columns)}
                {capture name=over_other_columns}
                    {strip}
                        {if !empty($other_columns_selected)}
                            {self_link sort_mode='NULL'}{tr}No Additional Sort{/tr}{/self_link}
                            <hr>
                        {/if}
                        {$other_columns}
                    {/strip}
                {/capture}
            {/if}

            {if !empty($other_columns_selected)}
                {if isset($nbCols)}
                    {$nbCols=$nbCols+1}
                {else}
                    {$nbCols=1}
                {/if}
                <th>
                    {if !empty($fgal_listing_conf.$other_columns_selected.description)}
                        {self_link _sort_arg=$sort_arg _sort_field=$other_columns_selected _title=$fgal_listing_conf.$other_columns_selected.description _class='tips'}
                            {$fgal_listing_conf.$other_columns_selected.name}
                        {/self_link}
                    {else}
                        {self_link _sort_arg=$sort_arg _sort_field=$other_columns_selected _title=$fgal_listing_conf.$other_columns_selected.name}
                            {$fgal_listing_conf.$other_columns_selected.name}
                        {/self_link}
                    {/if}
                </th>
            {/if}

            {if ( $prefs.use_context_menu_icon neq 'y' and $prefs.use_context_menu_text neq 'y' )
                or (isset($gal_info.show_action) && $gal_info.show_action eq 'y')}
                {if isset($nbCols)}
                    {$nbCols=$nbCols+1}
                {else}
                    {$nbCols=1}
                {/if}
                <th>
                    {tr}Actions{/tr}
                </th>
            {/if}
            
            {if ( $prefs.feature_categories eq 'y') and empty($from_wiki_page)}
                {if isset($nbCols)}
                    {$nbCols=$nbCols+1}
                {else}
                    {$nbCols=1}
                {/if}
                <th>
                    {tr}Categories{/tr}
                </th>
            {/if}


            {if ( !empty($other_columns) or !empty($other_columns_selected))}
                {if isset($nbCols)}
                    {$nbCols=$nbCols+1}
                {else}
                    {$nbCols=1}
                {/if}
                <th style="width: 1em">
                    {if !empty($other_columns)}
                        <a href='#' {popup fullhtml="1" text=$smarty.capture.over_other_columns trigger="click"} title="{tr}Other Sorts{/tr}">
                    {/if}
                    {icon name='ranking' alt="{tr}Other Sorts{/tr}" title=''}
                    {if !empty($other_columns)}
                        </a>
                    {/if}
                </th>
            {/if}
        </tr>


        {section name=changes loop=$files}

            {if ( ( ! isset($fileId) ) || $fileId == 0 ) || ( $fileId == $files[changes].id )}
                {if ( $prefs.use_context_menu_icon eq 'y' or $prefs.use_context_menu_text eq 'y' )
                    and (!isset($gal_info.show_action) or $gal_info.show_action eq 'y')}
                    {capture name=over_actions}
                        {strip}
                            {$file=$files[changes]}{* For fgal_context_menu.tpl. Cannot be an include parameter, because "file" is a reserved name. *}
                            {include file='fgal_context_menu.tpl' menu_icon=$prefs.use_context_menu_icon menu_text=$prefs.use_context_menu_text}
                        {/strip}
                    {/capture}
                {/if}

                {capture name=over_preview}{strip}
                    {if isset($files[changes].type) && $files[changes].type|truncate:6:'':true eq 'image/'}
                        <div class='opaque'>
                            <img src="{$files[changes].id|sefurl:thumbnail}">
                        </div>
                    {/if}
                {/strip}{/capture}

                {$nb_over_infos=0}
                {capture name=over_infos}
                    {strip}
                        <table class="table table-condensed">
                            {foreach item=prop key=propname from=$fgal_listing_conf}
                                {if isset($item.key)}
                                    {$propkey=$item.key}
                                {else}
                                    {$propkey="show_$propname"}
                                {/if}
                                {if not empty($files[changes].$propname)}
                                    {if $propname == 'share' && isset($files[changes].share.data)}
                                        {foreach item=tmp_prop key=tmp_propname from=$files[changes].share.data}
                                            {$email[]=$tmp_prop.email}
                                        {/foreach}
                                        {if $email and is_array($email)}{$propval=$email|join:','}{/if}
                                    {else}
                                        {$propval=$files[changes].$propname}
                                    {/if}
                                {else}
                                    {$propval = ''}
                                {/if}
                                {* Format property values *}
                                {if isset($propname) and ($propname eq 'created' or $propname eq 'lastModif' or $propname eq 'lastDownload')}
                                    {if empty($propval)}
                                        {$propval=''}
                                    {else}
                                        {if isset($gal_info.show_modtimedate) && $gal_info.show_modtimedate eq 'y'}
                                            {$propval=$propval|tiki_long_datetime}
                                        {else}
                                            {$propval=$propval|tiki_long_date}
                                        {/if}
                                    {/if}
                                {elseif $propname eq 'last_user' or $propname eq 'author' or $propname eq 'creator'}
                                    {$propval=$propval|username}
                                {elseif $propname eq 'size'}
                                    {$propval=$propval|kbsize:true}
                                {elseif $propname eq 'ocr_state'}
                                    {if $propval === '1'}
                                        {$propval='{tr}Finished processing{/tr}'}
                                    {elseif $propval === '2'}
                                        {$propval='{tr}Currently processing{/tr}'}
                                    {elseif $propval === '3'}
                                        {$propval='{tr}Queued for processing{/tr}'}
                                    {elseif $propval === '4'}
                                        {$propval='{tr}Processing stalled{/tr}'}
                                    {else}
                                        {$propval='{tr}No scheduled processing{/tr}'}
                                    {/if}
                                {elseif $propname eq 'backlinks' and ! empty($files[changes].nbBacklinks)}
                                    {$propval=$files[changes].nbBacklinks}
                                {elseif $propname eq 'description'}
                                    {$propval=$propval|nl2br}
                                {/if}

                                {if isset($gal_info.$propkey) and ( $gal_info.$propkey eq 'a' or $gal_info.$propkey eq 'o' )}
                                    <tr>
                                        <th class="text-end">
                                            {$fgal_listing_conf.$propname.name|escape}
                                        </th>
                                        <td>
                                            {$propval|escape}
                                        </td>
                                    </tr>
                                    {$nb_over_infos=$nb_over_infos+1}
                                {/if}
                            {/foreach}
                        </table>
                    {/strip}
                {/capture}

                {if $nb_over_infos gt 0}
                    {$over_infos=$smarty.capture.over_infos}
                {else}
                    {$over_infos=''}
                {/if}

                {$nb_over_share=0}
                {capture name=over_share}
                    {strip}
                        {if ! empty($files[changes].share.data)}
                            {foreach item=prop key=propname from=$files[changes].share.data}
                                <b>{$prop.email}</b>: {$prop.visit} / {$prop.maxhits}<br>
                                {$nb_over_share=$nb_over_share+1}
                            {/foreach}
                        {/if}
                    {/strip}
                {/capture}

                {if $nb_over_share gt 0}
                    {$over_share=$smarty.capture.over_share}
                {else}
                    {$over_share=''}
                {/if}


            <tr>

                {if $gal_info.show_checked ne 'n' and ($tiki_p_admin_file_galleries eq 'y' or $tiki_p_upload_files eq 'y')}
                    <td class="checkbox-cell">
                        {if isset($files[changes].isgal) && $files[changes].isgal eq 1}
                            {$checkname='subgal'}
                        {else}
                            {$checkname='file'}
                        {/if}
                        <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="{$checkname}[]" value="{$files[changes].id|escape}"
                        {if isset($smarty.request.$checkname) and $smarty.request.$checkname
                            and in_array($files[changes].id,$smarty.request.$checkname)}checked="checked"{/if}>
                    </td>
                {/if}

                {if ( $prefs.use_context_menu_icon eq 'y' or $prefs.use_context_menu_text eq 'y' )
                    and (!isset($gal_info.show_action) or $gal_info.show_action neq 'n')}
                    <td style="white-space: nowrap">
                        <a class="fgalname tips" title="{tr}Actions{/tr}" href="#" {popup fullhtml="1" center=true text=$smarty.capture.over_actions trigger="click"} style="padding:0; margin:0; border:0" role="button">
                            {icon name='wrench' alt="{tr}Actions{/tr}"}
                        </a>
                    </td>
                {/if}

                {if isset($show_parentName) and $show_parentName eq 'y'}
                    <td>
                        <a href="{$files[changes].galleryId|sefurl:'filegallery'}">{$files[changes].parentName|escape}</a>
                    </td>
                {/if}
                {if isset($show_thumb) and $show_thumb eq 'y'}
                    <td>
                        {if $files[changes].isgal == 0}
                            <a href="{if $absurl == 'y'}{$base_url}{/if}tiki-download_file.php?fileId={$files[changes].fileId}&display"><img src="{if $absurl == 'y'}{$base_url}{/if}tiki-download_file.php?fileId={$files[changes].fileId}&thumbnail"></a>
                        {/if}
                    </td>
                {/if}

                {foreach from=$fgal_listing_conf item=item key=propname}
                    {if isset($item.key)}
                        {$key_name=$item.key}
                    {else}
                        {$key_name="show_$propname"}
                    {/if}

                    {if isset($gal_info.$key_name)
                        and ( $gal_info.$key_name eq 'y' or $gal_info.$key_name eq 'a' or $gal_info.$key_name eq 'i' or $propname eq 'name'
                            or ( !empty($other_columns_selected) and $propname eq $other_columns_selected
                            )
                        )
                    }
                        {if isset($files[changes].$propname)}
                            {$propval=$files[changes].$propname|escape}
                        {/if}
                        {* build link *}
                        {capture assign=link}
                            {strip}
                                {if isset($files[changes].isgal) && $files[changes].isgal eq 1}
                                    {if empty($filegals_manager)}
                                        {$query = ''}
                                    {else}
                                        {$query = 'filegals_manager='|cat:$filegals_manager}
                                    {/if}
                                    {if not empty($insertion_syntax)}
                                        {if $query}{$query = $query|cat:'&'}{/if}
                                        {$query = $query|cat:'insertion_syntax='|cat:$insertion_syntax}
                                    {/if}
                                    href="{$files[changes].id|sefurl:'filegallery':$query}{$query|escape}"
                                {else}
                                    {if !empty($filegals_manager)}
                                        href="#" title="{tr}Click here to use the file{/tr}"
                                        {$mimeRegex="#`$allowedMimeTypes|replace:'*': '.'`#"}
                                        {if ! isset($allowedMimeTypes) || (isset($files[changes].type) && preg_match($mimeRegex, $files[changes].type))}
                                            onclick="window.opener.insertAt('{$filegals_manager}',processFgalSyntax('{$files[changes]|json_encode:JSON_HEX_QUOT|replace:'"':'&quot;'|replace:'\u0022':'\\\u0022'}'), false, false, true);checkClose();return false;"
                                        {/if}
                                    {elseif (isset($files[changes].p_download_files) and $files[changes].p_download_files eq 'y')
                                    or (!isset($files[changes].p_download_files) and $files[changes].perms.tiki_p_download_files eq 'y')}
                                        {if $gal_info.type eq 'podcast' or $gal_info.type eq 'vidcast'}
                                            href="{$prefs.fgal_podcast_dir}{$files[changes].path}" title="{tr}Download{/tr}"
                                        {elseif $prefs.h5p_enabled eq 'y' and isset($files[changes].type) and $files[changes].type eq 'application/zip' and preg_match('/\.h5p$/i', $files[changes].filename)}
                                            href="{service controller='h5p' action='embed' fileId=$files[changes].id}" title="{tr}View{/tr}"
                                        {else}
                                            href="{$files[changes].id|sefurl:file}" title="{tr}Download{/tr}"
                                        {/if}
                                    {/if}

                                    {if $smarty.capture.over_preview neq ''
                                            and (((isset($files[changes].p_download_files)
                                                and $files[changes].p_download_files eq 'y')
                                            or (!isset($files[changes].p_download_files)
                                            and $files[changes].perms.tiki_p_download_files eq 'y')))}
                                        {literal} {/literal}{popup fullhtml="1" text=$smarty.capture.over_preview}
                                    {/if}
                                {/if}
                            {/strip}
                        {/capture}

                        {* Format property values *}
                        {if $propname eq 'id' or $propname eq 'name'}
                            {if $propname eq 'name' and $propval eq '' and $gal_info.show_name eq 'n'}
                                {* show the filename if only name should be displayed but is empty *}
                                {$propval=$files[changes].filename}
                                {$propval="<a class='fgalname namealias' $link>$propval</a>"}
                            {else}
                                {$propval="<a class='fgalname' $link>$propval</a>"}
                            {/if}
                            {if $propname eq 'name' and $gal_info.show_name eq 'n' and $gal_info.show_description eq 'o'}
                                {if $gal_info.max_desc gt 0}
                                    {$desc=$files[changes].description|truncate:$gal_info.max_desc:"...":false|nl2br}
                                {else}
                                    {$desc=$files[changes].description|nl2br}
                                {/if}
                                {$propval="$propval<br><span class=\"description\">`$desc`</span>"}
                            {/if}
                        {elseif $propname eq 'created' or $propname eq 'lastModif' or $propname eq 'lastDownload'}
                            {if empty($propval)}
                                {$propval=''}
                            {else}
                                {if isset($gal_info.show_modtimedate) && $gal_info.show_modtimedate eq 'y'}
                                    {$propval=$propval|tiki_short_datetime}
                                {else}
                                    {$propval=$propval|tiki_short_date}
                                {/if}
                            {/if}
                        {elseif $propname eq 'last_user' or $propname eq 'author' or $propname eq 'creator'}
                            {$propval=$propval|userlink}
                        {elseif $propname eq 'size'}
                            {$propval=$propval|kbsize:true}
                        {elseif $propname eq 'type'}
                            {if isset($files[changes].isgal) && $files[changes].isgal eq 1}
                                {capture assign=propval}{icon name='file-archive-open' class=''}{/capture}
                            {else}
                                {$propval=$files[changes].filename|iconify:$files[changes].type:$files[changes].id}
                            {/if}
                        {elseif $propname eq 'description' and $gal_info.max_desc gt 0}
                            {$propval=$propval|truncate:$gal_info.max_desc:"...":false|nl2br}
                        {elseif $propname eq 'description'}
                            {$propval=$propval|nl2br}
                        {elseif $propname eq 'ocr_state'}
                            {if $propval === '1'}
                                {capture assign=propval}{icon style='outline' name='check-circle' title='{tr}Finished processing{/tr}'}{/capture}
                            {elseif $propval === '2'}
                                {capture assign=propval}{icon style='outline' name='sync' title='{tr}Currently processing{/tr}'}{/capture}
                            {elseif $propval === '3'}
                                {capture assign=propval}{icon style='outline' name='circle' title='{tr}Queued for processing{/tr}'}{/capture}
                            {elseif $propval === '4'}
                                {capture assign=propval}{icon style='outline' name='pause-circle' title='{tr}Processing stalled{/tr}'}{/capture}
                            {else}
                                {capture assign=propval}{icon style='outline' name='times-circle' title='{tr}No scheduled processing{/tr}'}{/capture}
                            {/if}
                        {elseif $propname eq 'lockedby' and $propval neq ''}
                            {if $gal_info.show_lockedby eq 'i' or $gal_info.show_lockedby eq 'a'}
                                {$propval=$propval|username}
                                {capture assign=propval}{icon name='lock' class='tips' title=":{tr}Locked by-{/tr} "|cat:$propval}{/capture}
                            {else}
                                {$propval=$propval|userlink}
                            {/if}
                        {elseif $propname eq 'backlinks'}
                            {if empty($files[changes].nbBacklinks)}
                                {$propval=''}
                            {else}
                                {$propval=$files[changes].nbBacklinks}
                                {$fid=$files[changes].id}
                                {$propval="<a class='ajaxtips' href='list-file_backlinks_ajax.php?fileId=$fid' data-ajaxtips='list-file_backlinks_ajax.php?fileId=$fid'>$propval</a>"}
                            {/if}
                        {elseif $propname eq 'deleteAfter'}
                            {if empty($files[changes].deleteAfter)}
                                {$propval="-"}
                            {else}
                                {$limitdate=$files[changes].deleteAfter+$files[changes].lastModif}
                                {$propval=$limitdate|tiki_remaining_days_from_now:$prefs.short_date_format}
                            {/if}
                        {elseif $propname eq 'share'}
                            {if isset($files[changes].share)}
                                {$share_string=$files[changes].share.string}
                                {$share_nb=$files[changes].share.nb}
                                {capture assign=share_capture}
                                    {strip}
                                        <a class='fgalname tips' title="{tr}Share{/tr}" href='#' {popup fullhtml=1 text=$over_share left=true trigger="click"} style='cursor:help'>
                                            {icon name='group' alt=''}
                                        </a> ({$share_nb}) {$share_string}
                                    {/strip}
                                {/capture}
                                {$propval=$share_capture}
                            {/if}
                            {elseif $propname eq 'hits'}
                            {if $prefs.fgal_list_hits eq 'y'}
                                {if $prefs.fgal_list_ratio_hits eq 'y'}
                                    {$hits=$files[changes].hits}
                                    {$maxhits=$files[changes].maxhits}
                                    {if $maxhits <= 0}
                                        {$propval=$hits}
                                    {else}
                                        {$propval="$hits / <b>$maxhits</b>"}
                                    {/if}
                                {else}
                                    {$propval=$files[changes].hits}
                                {/if}
                            {/if}
                        {/if}
                        {if $propname eq 'name' and ( $gal_info.show_name eq 'a' or $gal_info.show_name eq 'f' )}
                            <td>
                                {if $link neq ''}<a class='fgalname fileLink' fileId='{$files[changes].id}' type='{if isset($files[changes].type)}{$files[changes].type}{/if}' {$link}>{/if}{if isset($files[changes].filename)}{$files[changes].filename|escape}{/if}{if $link neq ''}</a>{/if}
                            </td>
                        {/if}

                        {if !empty($other_columns_selected) and $propname eq $other_columns_selected}
                            {$other_columns_selected_val=$propval}
                        {else}
                            {if !(empty($galleryId) and $propname eq 'lockedby') and ($propname neq 'name'
                                or ( $gal_info.show_name eq 'a' or $gal_info.show_name eq 'n' ))
                                and ($propname neq 'description' or $gal_info.show_name neq 'n'
                                    or $gal_info.show_description eq 'y' or $gal_info.show_description eq 'a')}
                                <td>{$propval}</td>
                            {/if}
                        {/if}
                    {/if}
                    {$propval = null}
                {/foreach}

                {if !empty($other_columns_selected_val)}
                    <td>
                        {$other_columns_selected_val}
                    </td>
                {/if}

                {if ( $prefs.use_context_menu_icon neq 'y' and $prefs.use_context_menu_text neq 'y' )
                    or (isset($gal_info.show_action) and $gal_info.show_action eq 'y')}
                    {$file=$files[changes]}{* For fgal_context_menu.tpl. Cannot be an include parameter, because "file" is a reserved name. *}
                    <td>{include file='fgal_context_menu.tpl'}</td>
                {/if}
                {if ( $prefs.feature_categories eq 'y') and empty($from_wiki_page)}
                    <td>
                    {if !empty($files[changes].categories)}
                        {foreach from=$files[changes].categories item=cat name=cat}
                            <a href="tiki-browse_categories.php?parentId={$cat.categId|escape}">
                                {$cat.name|escape}
                            </a>{if not $smarty.foreach.cat.last}, {/if}
                        {/foreach}
                    {else}
                        <span class="text-muted">{tr}Uncategorized{/tr}</span>
                    {/if}
                    </td>
                {/if}

                {if (isset($other_columns) and $other_columns neq '') or (isset($other_columns_selected) and $other_columns_selected neq '')}
                    <td>
                        {if $show_infos eq 'y'}
                            {if $over_infos eq ''}
                                {icon name='minus' class='tips' title=":{tr}No information{/tr}"}
                            {else}
                                <a class="fgalname tips left" href="#" onclick="return false;" title="{tr}Information{/tr}" {popup fullhtml="1" text=$over_infos|replace:'&amp;':'&' left=true} style="cursor:help" role="button">
                                    {icon name='information' class='' title=''}
                                </a>
                            {/if}
                        {/if}
                    </td>
                {/if}
            </tr>
        {/if}
        {sectionelse}
            {norecords _colspan=$nbCols}
        {/section}
        {if !empty($files) and $gal_info.show_checked ne 'n' and $tiki_p_admin_file_galleries eq 'y'
            and $view neq 'page'}
            <tr>
                <td colspan="{$nbCols}">
                    {select_all checkbox_names='file[],subgal[]' label="{tr}Select All{/tr}"}
                </td>
            </tr>
        {/if}


    </table>
</div>
