<?php
define('ACCEPT_IP','127.0.0.1');
define('MONITORED_DIR','');//The directory where the .git directory is located. Leave blank if this script is in the same directory as the .git file
define('MAIL_TO','siddhant3s@gmail.com'); //mail the report to this email address. Leave blank to disable
define('LOG_FILE','_ghupdater.log'); //Log file. Highly recommended
define('CONFIG_FILE','ghupdater.ini');
//////////////////////////Disable the effect of magic_quotes_gpc////////////////
if (get_magic_quotes_gpc()) {
  $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
  while (list($key, $val) = each($process)) {
    foreach ($val as $k => $v) {
      unset($process[$key][$k]);
      if (is_array($v)) {
	$process[$key][stripslashes($k)] = $v;
	$process[] = &$process[$key][stripslashes($k)];
      } else {
	$process[$key][stripslashes($k)] = stripslashes($v);
      }
    }
  }
  unset($process);
}
////////////////////////////////////////////////////////////////////////////////
function copyemz($file1,$file2){
  $contentx =@file_get_contents($file1);
  $openedfile = fopen($file2, "w");
  fwrite($openedfile, $contentx);
  fclose($openedfile);
  if ($contentx === FALSE) {
    $status=false;
  }else $status=true;
                   
  return $status;
} 
$config = json_decode(file_get_contents(CONFIG_FILE), true);
$rep_path= isset($config['repodir'])?$config['repodir'] : './';
$templog='';
function  fetch_and_put_files($file_status, $commit_raw_url) {
  global $rep_path;
  foreach($file_status as $f => $status)
    {
      $local_file=$rep_path.'/'.$f;
      if($status)
	{
	  logmsg("Now Downloading file $f");
                        
	  $dir=dirname($local_file);
	  if (!is_dir($dir))
	    mkdir($dir,0777,true) or logmsg("Cannot make directory".$dir) or exit();
                         
	  //TODO Uncomment the following line
	  copyemz($commit_raw_url.$f,$local_file) or logmsg("Unable to run copy command") or exit();
	  logmsg("Download complete for file $f");
	}
      else 
	{
	  logmsg("Now Removing $f");
	  if(file_exists($local_file))
	    {
	      unlink($local_file) or logmsg("Cannot remove file $localfile");
	      logmsg("File removed $f");
	    }
	}
        
    }
}

function logmsg($msg)
{ 
  global $templog;
  echo ($msg.'<br>');
  $templog.="$msg<br>\n";
  return;
}
        

//echo $_POST['payload'];$comit_obj->{'id'}
if(!isset($_POST['payload'])) {
  if(!isset($_POST['email'])) {
    ?>
    <html>
	<form action = '' method='POST'>
	<table>
	<tr>
	<td style="white-space:nowrap;width:30%;">E-mail Address:</td>
	<td><input  type="email" name="email" value="<?php echo $config['email'] ?>" ></td>
	</tr>
	<tr>
	<td>&nbsp;</td>
		      <td> If provided, an email will be sent with a concise log after every push</td>
		      </tr>
		      <tr>
		      <td>Repo Directory:</td>
		      <td><input type="text" name="dir" value="<?php echo $config['repodir'] ?>"> </td>
		      </tr>
		      <tr>
		      <td>&nbsp;</td>
				    <td>Directory where the repo will be deployed</td>
				    </tr>

				    <tr>
				    <td><input type="submit" value="Save" ></td>
				    </tr>
				    </table> 
				    </form>


				    </html>
				    <?php
				    } else {

      // Write the configuration
      $config = array(
		      'email' => $_POST['email'],
		      'repodir' => $_POST['dir']
		      );
      $config_str = json_encode($config);
      file_put_contents(CONFIG_FILE, $config_str);
      echo "Saved";
      if($_POST['import']) {
      }


  }
}
else {

  echo 'REP_PATH:'.$rep_path;
  $obj=json_decode($_POST['payload']);
  if(!$obj) die("Unable to parse the payload");
  //var_dump($obj);
  $commitlist=$obj->{'commits'};
  $username=$obj->{'repository'}->{'owner'}->{'name'};
  $reponame=$obj->{'repository'}->{'name'};
  $gh_raw_url='https://raw.github.com/' . $username . '/' . $reponame . '/';
  $file_status = array();
  //print $gh_url;
  foreach($commitlist as $commit_obj)
    {
      $id=$commit_obj->{'id'};
      $message=$commit_obj->{'message'};
      $commit_raw_url=$gh_raw_url.$id.'/';
      logmsg("Processing commit: $id || Message:$message");
      $files_added_modified=array_merge($commit_obj->{'added'},$commit_obj->{'modified'});
      $files_removed = $commit_obj->{'removed'};
        
      if($files_added_modified)
        {
	  foreach($files_added_modified as $f)
            {   
              $file_status[$f]=1;  
            }
        }
      if($files_removed)
        {
          foreach($files_removed as $f)
	    {   
	      $file_status[$f]=0;  
	    }
	}
    }
  fetch_and_put_files($file_status, $commit_raw_url);
  echo('<br/><br/><br/>');
  if(isset($config['email']))
    {
      $email_addr = $config['email'];
      mail($email_addr,'gitupdater.php recieved a push',$templog);
    }
  error_log($templog);
}
?>
