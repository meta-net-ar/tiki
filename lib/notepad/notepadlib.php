<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

/**
 * Library for managing user personal notes.
 *
 * Provides functionality for creating, reading, updating, and deleting
 * personal text notes stored in the tiki_user_notes table.
 * Notes are user-scoped (each user can only access their own notes).
 *
 * @see TikiLib::replace_note() For creating and updating notes
 */
class NotepadLib extends TikiLib
{
    /**
     * Retrieve a single note by ID for a specific user.
     *
     * @param string $user    The username of the note owner
     * @param int    $noteId  The unique identifier of the note
     *
     * @return array|false Note data array with keys (noteId, user, name, data, created,
     *                     lastModif, size, parse_mode) or false if not found
     */
    public function get_note(string $user, int $noteId): array|false
    {
        $query = "select * from `tiki_user_notes` where `user`=? and `noteId`=?";
        $result = $this->query($query, [$user,(int)$noteId]);
        $res = $result->fetchRow();
        return $res;
    }

    /**
     * Update the parsing mode for a note.
     *
     * @param string $user    The username of the note owner
     * @param int    $noteId  The unique identifier of the note
     * @param string $mode    The parse mode ('raw' for plain text, 'wiki' for wiki syntax)
     *
     * @return bool Always returns true
     */
    public function set_note_parsing(string $user, int $noteId, string $mode): bool
    {
        $query = "update `tiki_user_notes` set `parse_mode`=? where `user`=? and `noteId`=?";
        $this->query($query, [$mode,$user,(int)$noteId]);
        return true;
    }

    /**
     * Delete a note for a specific user.
     *
     * @param string $user    The username of the note owner
     * @param int    $noteId  The unique identifier of the note to delete
     *
     * @return void
     */
    public function remove_note(string $user, int $noteId): void
    {
        $query = "delete from `tiki_user_notes` where `user`=? and `noteId`=?";
        $this->query($query, [$user,(int)$noteId]);
    }

    /**
     * List notes for a user with pagination, sorting, and optional search.
     *
     * @param string $user        The username of the note owner
     * @param int    $offset      Number of records to skip (for pagination)
     * @param int    $maxRecords  Maximum number of records to return
     * @param string $sort_mode   Sort order (e.g., 'lastModif_desc', 'name_asc', 'created_desc')
     * @param string $find        Optional search string to filter notes by name or content
     *
     * @return array{data: array, count: int} Array with 'data' (list of notes with calculated size) and 'count' (total number of matching notes)
     */
    public function list_notes(string $user, int $offset, int $maxRecords, string $sort_mode, string $find): array
    {
        $bindvars = [$user];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " and (`name` like ? or `data` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = "";
        }

        $query = "select * from `tiki_user_notes` where `user`=? $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_user_notes` where `user`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res['size'] = strlen($res['data']);

            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }
}

$notepadlib = new NotepadLib();
