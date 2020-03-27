<?php

function get_last_commit_number(){
	exec("git log -1 --pretty=%h", $returnArray, $returnValue);
	return $returnArray[0];
}

function has_uncommited_changes($localPath){
	$localPath = escapeshellarg($localPath);
	exec("git status -s $localPath", $returnArryay);
	return count($returnArryay) >0;
}

function git_add($file){
	$file = escapeshellarg($file);
	exec("git add $file");
}

function commit_all($msg){
	$msg = escapeshellarg($msg);
	if (has_uncommited_changes(".")){
		error("Commit seems to have failed. There is no changes in the working tree");
	}else{
		git_add(".");
		exec("git commit -m $msg", $returnArray);
		return get_last_commit_number();
	}
}
function commit_specific_files($messages,$localpath){
	$messages = escapeshellarg($messages);
	$localpath = escapeshellarg($localpath);

	if (has_uncommited_changes($localpath)){
		error("Commit seems to have failed. There is no changes in the working tree");
	}else{
		git_add($localpath);
		exec("git commit -m $messages $localpath", $returnArray);
		return get_last_commit_number();
	}
}
