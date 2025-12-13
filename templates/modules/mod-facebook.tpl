{if $prefs.feature_socialnetworks eq 'y'}
    {if ! empty($module_params.rows)}
        {$rows = $module_params.rows}
    {else}
        {$rows = ''}
    {/if}
    {tikimodule error=$module_params.error title=$tpl_module_title name="facebook" flip=$module_params.flip rows=$rows decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

    <ul class="fb-module-feed">
        {section name=ix loop=$timeline}
            <li class="fb-post fb-post-{$timeline[ix].post_type}">
                {* Author name *}
                {if ! empty($module_params.showuser) && $module_params.showuser eq 'y' && ! empty($timeline[ix].fromName)}
                    <div class="fb-author">
                        {$timeline[ix].fromName}
                    </div>
                {/if}

                {* Message text *}
                {if ! empty($timeline[ix].message)}
                    <div class="fb-text">
                        {$timeline[ix].message|escape|nl2br}
                    </div>
                {/if}

                {* Image (displayed only once) *}
                {if ! empty($timeline[ix].has_image)}
                    <div class="fb-image">
                        <a href="{$timeline[ix].link}" target="_blank">
                            <img src="{$timeline[ix].image}" alt="Facebook post" loading="lazy">
                        </a>
                    </div>
                {/if}

                {* Attachments (for shared links, etc. - not photos to avoid duplication) *}
                {if ! empty($timeline[ix].attachments)}
                    {foreach from=$timeline[ix].attachments item=att}
                        {if ! empty($att.title) || ! empty($att.description)}
                            <div class="fb-attachment-link">
                                {if ! empty($att.title)}
                                    <div class="fb-att-title">
                                        {if ! empty($att.url)}
                                            <a href="{$att.url}" target="_blank">{$att.title}</a>
                                        {else}
                                            {$att.title}
                                        {/if}
                                    </div>
                                {/if}
                                {if ! empty($att.description)}
                                    <div class="fb-att-desc">
                                        {$att.description|truncate:150}
                                    </div>
                                {/if}
                            </div>
                        {/if}
                    {/foreach}
                {/if}

                {* Post metadata and action button *}
                <div class="fb-footer">
                    <div class="fb-meta">
                        <span class="fb-type">
                            {if $timeline[ix].post_type eq 'photo'}📷 Photo
                            {elseif $timeline[ix].post_type eq 'video'}🎥 Video
                            {elseif $timeline[ix].post_type eq 'link'}🔗 Link
                            {elseif $timeline[ix].post_type eq 'status'}💬 Status
                            {else}{$timeline[ix].post_type|capitalize}
                            {/if}
                        </span>
                        <span>·</span>
                        <span class="fb-date">
                            {$timeline[ix].created_time|tiki_short_datetime}
                        </span>
                    </div>
                    {if ! empty($timeline[ix].link)}
                        <div class="fb-action" style="margin-top: 8px;">
                            <a href="{$timeline[ix].link}" target="_blank" class="fb-see-more">
                                {icon name="facebook"} See more on Facebook {icon name="external-link-alt"}
                            </a>
                        </div>
                    {/if}
                </div>
            </li>
        {/section}
    </ul>

    {* Pagination *}
    {if $fb_page > 1 || $fb_has_posts}
        <div class="fb-pagination">
            {if $fb_page > 1}
                {self_link fb_page="{$fb_page - 1}" _class="btn btn-secondary btn-sm"}{icon name="chevron-left"} Previous{/self_link}
            {/if}
            <span style="color: #65676b; font-size: 14px; margin: 0 8px;">Page {$fb_page}</span>
            {if $fb_has_posts}
                {self_link fb_page="{$fb_page + 1}" _class="btn btn-secondary btn-sm"}Next {icon name="chevron-right"}{/self_link}
            {/if}
        </div>
    {/if}

    {/tikimodule}
{/if}
