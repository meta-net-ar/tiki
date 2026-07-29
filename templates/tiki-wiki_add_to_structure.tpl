<nav class="nav-breadcrumb mb-2" aria-label="{tr}Structures{/tr}">
    <div class="d-flex flex-row flex-wrap align-items-center">
        <span class="mt-2 me-3 p-1 d-inline-flex align-items-center">
            {icon name="structure"}
            <span class="ms-2">{tr}Add to a structure:{/tr}</span>
        </span>
        <ol class="breadcrumb mt-2 me-3 p-1 d-inline-flex align-items-center flex-wrap mb-0">
            {foreach from=$structuresToAdd item=struct_info name=addstruct}
                <li class="breadcrumb-item pe-2">
                    {self_link _script="tiki-edit_structure.php" page_ref_id=$struct_info.page_ref_id find_objects=$page search_objects=Filter _class="btn btn-outline-primary btn-sm" _title="{tr}Add this page to the structure{/tr}" _ajax="n"}
                        {if !empty($struct_info.page_alias)}{$struct_info.page_alias|escape}{else}{$struct_info.pageName|pagename}{/if}
                    {/self_link}
                </li>
            {/foreach}
            {if $tiki_p_edit eq 'y'}
                <li class="breadcrumb-item pe-2">
                    {self_link _script="tiki-editpage.php" page=$page _class="btn btn-link btn-sm p-0" _title="{tr}Open the Structures tab in the page editor{/tr}" _ajax="n"}
                        {icon name="edit"} {tr}More options{/tr}
                    {/self_link}
                </li>
            {/if}
        </ol>
    </div>
</nav>
