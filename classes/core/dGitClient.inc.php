<?php
// Quick guide:
// --> On Windows, install MsysGit (https://msysgit.github.io/)
// --> Default path is Program Files (x86), equivalent to C:/Progra~2.
// 
// Syntax:
//   dGitClient::exec("whoami");
//   dGitClient::exec("git status");
//   dGitClient::exec("ssh -v");
// 
class dGitClient{
	static $winShPath = "C:/Progra~2/Git/bin/sh.exe";
	static $baseDir   = false;
	Function exec($git_cmd){
		if(!self::$baseDir){
			self::$baseDir = dirname(__FILE__);
		}
		
		if(substr(PHP_OS, 0, 3) != 'WIN'){
			chdir(self::$baseDir);
			$ret = exec($git_cmd);
		}
		else{
			if(!self::$winShPath){
				echo 'Windows detected, but no dGitClient::$winShPath defined. Install MINGW and set the path.';
				return false;
			}
			elseif(!file_exists(self::$winShPath)){
				echo 'Windows detected, but SH.EXE not found at '.(self::$winShPath).'. Please check and try again.';
				return false;
			}
			
			$pipes = Array();
			$plist = Array(
			   0 => Array("pipe", "r"),  // stdin is a pipe that the child will read from
			   1 => Array("pipe", "w"),  // stdout is a pipe that the child will write to
			   2 => Array("pipe", "w")   // stderr is a file to write to
			);
			$proc  = proc_open(self::$winShPath." --login -i", $plist, $pipes, self::$baseDir);
			if(!is_resource($proc)){
				echo "proc_open failed..\r\n";
				return false;
			}
			
			fwrite($pipes[0], $git_cmd);
			fclose($pipes[0]);
			
			$ret = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			
			$errDump = stream_get_contents($pipes[2]);
			if($errDump){
				$errDump = preg_replace("/\r?\n?".chr(27)."[\[\]].+?\n\\$.+?\n/s", "", $errDump);
				$ret .= ($ret?"\r\n":"").$errDump;
			}
			
			fclose($pipes[2]);
			proc_close($proc);
		}
		
		$ret = preg_replace("/^Welcome to Git.+?Run 'git help <command>' to display help for specific commands.\n/s", "", $ret);
		return $ret;
	}
}

