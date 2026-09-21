{title help="User Tasks" url="tiki-user_tasks.php"}{tr}Tasks{/tr}{/title}

<div class="t_navbar mb-4 d-flex align-items-center gap-2">
    {button href="tiki-user_tasks.php?show_form=y" _class="btn btn-primary" _text="{tr}New task{/tr}"}
    
    <div class="dropdown d-inline-block">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="moreTaskActions" data-bs-toggle="dropdown" aria-expanded="false">
            {tr}More{/tr}
        </button>
        <ul class="dropdown-menu" aria-labelledby="moreTaskActions">
            {if $task_admin}
                <li>
                {if $admin_mode}
                    <a class="dropdown-item" href="tiki-user_tasks.php?taskId={$taskId}&amp;admin_mode_off=on">{tr}Admin mode off{/tr}</a>
                {else}
                    <a class="dropdown-item" href="tiki-user_tasks.php?taskId={$taskId}&amp;admin_mode=on">{tr}Admin mode on{/tr}</a>
                {/if}
                </li>
            {/if}
            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#taskHelpModal">{tr}Task help{/tr}</button></li>
            {if (not $show_form)}
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#emptyTrashModal">{tr}Empty trash{/tr}</button></li>
            {/if}
        </ul>
    </div>
</div>

<div class="modal fade" id="taskHelpModal" tabindex="-1" aria-labelledby="taskHelpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="taskHelpModalLabel">{tr}Task help{/tr}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table norml table-striped table-hover">
            {if $tiki_p_tasks_receive eq 'y'}
                <tr>
                    <td>
                        {icon name='login' class='tips' title=":{tr}Task received{/tr}"}
                    </td>
                    <td>
                        {tr}You received this task{/tr}.
                    </td>
                </tr>
            {/if}
            {if $tiki_p_tasks_send eq 'y'}
                <tr>
                    <td>
                        {icon name='logout' class='tips' title=":{tr}Task sent{/tr}"}
                    </td>
                    <td>
                        {tr}You sent this task to another user{/tr}.
                    </td>
                </tr>
            {/if}
            {if $tiki_p_tasks_receive eq 'y' or $tiki_p_tasks_send eq 'y'}
                <tr>
                    <td>
                        {icon name='group' class='tips' title=":{tr}Task shared by a group{/tr}"}
                    </td>
                    <td>
                        {tr}Task is shared by a group{/tr}.
                    </td>
                </tr>
            {/if}
            <tr>
                <td>
                    {icon name='ok' class='tips' title=":{tr}Accepted by task user and creator{/tr}"}
                </td>
                <td>
                    {tr}Task has been accepted by user and creator{/tr}.
                </td>
            </tr>
            <tr>
                <td>
                    {icon name='remove' class='tips' title=":{tr}Rejected by a user{/tr}"}
                </td>
                <td>
                    {tr}Task has been rejected by a user{/tr}.
                </td>
            </tr>
            {if $tiki_p_tasks_receive eq 'y'}
                <tr>
                    <td>
                        {icon name='history' class='tips' title=":{tr}Waiting for me{/tr}"}
                    </td>
                    <td>
                        {tr}Task has not yet been accepted or rejected by you{/tr}.
                    </td>
                </tr>
            {/if}
            {if $tiki_p_tasks_receive eq 'y' or $tiki_p_tasks_send eq 'y'}
                <tr>
                    <td>
                        {icon name='user' class='tips' title=":{tr}Waiting for other user{/tr}"}
                    </td>
                    <td>
                        {tr}Task has not yet been accepted or rejected by another user{/tr}.
                    </td>
                </tr>
            {/if}
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="emptyTrashModal" tabindex="-1" aria-labelledby="emptyTrashModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="emptyTrashModalLabel">{tr}Empty Trash{/tr}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        {tr}Are you sure you want to permanently delete all tasks in the trash? This action cannot be undone.{/tr}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{tr}Cancel{/tr}</button>
        <a href="tiki-user_tasks.php?emty_trash=Empty trash" class="btn btn-danger">{tr}Empty trash{/tr}</a>
      </div>
    </div>
  </div>
</div>


{if $admin_mode}<div align="center"><a class="highlight" >{tr}Admin Mode{/tr}</a></div><br>{/if}
{* start ************ view ***************}
{if (($tiki_view_mode eq 'view') or ($tiki_view_mode eq 'preview'))}
    {include file='tiki-user_tasks_view.tpl'}
{/if}

{* end ************ View task ***************}

{* start ************ Edit Form ***************}

{if ($show_form)}
    {include file='tiki-user_tasks_edit.tpl'}
{/if}

{* end ************ Edit Form ***************}

{* start ************ Task list ***************}
{if ( not $show_form )}
    {include file='tiki-user_tasks_list.tpl'}
{/if}

{* end ************ Task list ***************}

<br>
<br>
{* start ************ Search ***************}
