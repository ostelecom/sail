<?php
//
// Developed by CoCo
// Copyright (C) 2012 CoCoSoft
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
require_once "../srkPageClass";
require_once "../srkDbClass";
require_once "../srkHelperClass";
require_once "../formvalidator.php";


Class app {
	
	protected $message; 
	protected $myPanel;
	protected $dbh;
	protected $helper;
	protected $validator;
	protected $invalidForm;
	protected $error_hash = array();

public function showForm() {
	
	$this->myPanel = new page;
	$this->dbh = DB::getInstance();
	$this->helper = new helper;
		
	echo '<body>';
	echo '<form id="sarkappForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;
	
	$this->myPanel->pagename = 'Custom Apps';
	
	if (isset($_POST['new_x'])) { 
		$this->showEdit('new');
		return;
	}

	if (isset($_POST['update_x'])) { 
		$this->saveEdit();
		if ($this->invalidForm) {
			$this->showEdit($_POST['pkey']);
			return;
		}					
	}	
	
	if (isset($_GET['edit'])) { 
		$this->showEdit();	
		return;
	}		
/*
	if (isset($_POST['save_x'])) { 
		$this->saveNew();
		if ($this->invalidForm) {
			$this->showNew();
			return;
		}					
	}
*/	
	if (isset($_POST['commit_x']) || isset($_POST['commitClick_x'])) { 
		$this->helper->sysCommit();
		$this->message = "Updates have been Committed";	
	}
	
	$this->showMain();
	
	$this->dbh = NULL;
	return;
	
}
	
private function showMain() {
	
	if (isset($this->message)) {
		$this->myPanel->msg = $this->message;
	} 

/* 
 * start page output
 */
  
	echo '<div class="buttons">';	
	$this->myPanel->Button("new");
	$this->myPanel->commitButton();
	echo '</div>';	
	
	$this->myPanel->Heading();
	
	$tabname = 'apptable';
	if ( $_SERVER['REMOTE_USER'] == 'admin' ) {
		$tabname .= 'admin';
	}
	
	echo '<div class="datadivnarrow">';
	
	echo '<table class="display" id="' . $tabname . '"  >' ;	

	echo '<thead>' . PHP_EOL;	
	echo '<tr>' . PHP_EOL;
	

	$this->myPanel->aHeaderFor('context');
	$this->myPanel->aHeaderFor('cluster'); 
	$this->myPanel->aHeaderFor('description'); 	
	$this->myPanel->aHeaderFor('appspan');
	$this->myPanel->aHeaderFor('ed');
	$this->myPanel->aHeaderFor('del');
	
	echo '</tr>' . PHP_EOL;
	echo '</thead>' . PHP_EOL;
	echo '<tbody>' . PHP_EOL;
		
/*** table rows ****/

	$rows = $this->helper->getTable("appl");
	foreach ($rows as $row ) { 
		echo '<tr name="linekey" id="' . $row['pkey'] . '">'. PHP_EOL; 
		echo '<td class="read_only">' . $row['pkey'] . '</td>' . PHP_EOL;			
		echo '<td >' . $row['cluster']  . '</td>' . PHP_EOL;		 
		echo '<td >' . $row['desc']  . '</td>' . PHP_EOL;	
		echo '<td >' . $row['span']  . '</td>' . PHP_EOL;	
		$get = '?edit=yes&amp;pkey=';
		$get .= $row['pkey'];	
		$this->myPanel->editClick($_SERVER['PHP_SELF'],$get);
		$get = '?id=' . $row['pkey'];		
		$this->myPanel->ajaxdeleteClick($get);		echo '</td>' . PHP_EOL;
		echo '</tr>'. PHP_EOL;
	}

	echo '</tbody>' . PHP_EOL;
	echo '</table>' . PHP_EOL;
	echo '</div>';
		
}

private function showEdit($key=False) {
	$tuple=array();
	if ($key != False) {
		if ($key == 'new') {
			$pkey = 'NewApp' . rand(1000, 9999);
			$tuple['pkey'] 	= $pkey;
			$ret = $this->helper->createTuple("appl",$tuple);
		}
		else {
			$pkey=$key;
		}
	}
	else {
		$pkey = $_GET['pkey'];
	}
	
	$app = $this->dbh->query("SELECT * FROM appl WHERE pkey = '" . $pkey . "'")->fetch(PDO::FETCH_ASSOC);

	$printline = "APP " . $pkey;
	$this->myPanel->msg .= $printline; 
	
	if (isset($this->message)) {
		$this->myPanel->msg .= $this->message;
	} 
	
	$xref = $this->xRef($pkey);

	echo '<div class="buttons">';
	$this->myPanel->Button("cancel");
	$this->myPanel->override = "update";
	$this->myPanel->Button("save");
	echo '</div>';	
		
	$this->myPanel->Heading();
	if (isset($this->message)) {	
		foreach($this->error_hash as $inpname => $inp_err) {
			echo "<p>$inpname : $inp_err</p>\n";
		}       
	}

	echo '<div class="datadivtabedit">'; 	
    echo '<div id="pagetabs" >' . PHP_EOL;
    echo '<ul>' . PHP_EOL;
    echo '<li><a href="#general">Code</a></li>' . PHP_EOL;
    echo '<li><a href="#xref" >XREF</a></li>' . PHP_EOL;
    echo '</ul>' . PHP_EOL;

#
#   TAB XREF table
#
    echo '<div id="xref" >' . PHP_EOL;
    echo '<span style="color: rgb(0, 0, 0); font-weight:bold; font-size:small; ">Cross References to this IVR</span><br />' . PHP_EOL;
    echo '<br/>'. PHP_EOL;
    echo "$xref";
    echo '<br/>'. PHP_EOL;
    echo '</div>' . PHP_EOL;
#
#       TAB DIVEND
#

#
#   TAB general
#
    echo '<div id="general" >' . PHP_EOL;
    $this->myPanel->aLabelFor('context');
	echo '<input type="text" name="newkey" size="20" id="newkey" value="' . $pkey . '"  />' . PHP_EOL;	
	$this->myPanel->aLabelFor('appspan');
	$this->myPanel->selected = $app['span'];
	$this->myPanel->popUp('span', array('Internal','External','Both','Neither'));		
	$this->myPanel->aLabelFor('striptags');
    $this->myPanel->selected = $app['striptags'];
    $this->myPanel->popUp('striptags', array('YES','NO'));
	echo '<p><textarea class="appbox" name="extcode" id="extcode">' . $app['extcode'] . '</textarea></p>' . PHP_EOL;
	echo '</div>' . PHP_EOL;

#
#  end of TABS DIV
#
   echo '</div>' . PHP_EOL;
   echo '</div>' . PHP_EOL;
    
   echo '<input type="hidden" name="pkey" id="pkey" value="' . $app['pkey'] . '"  />' . PHP_EOL; 
   
			
}


private function saveEdit() {
// save the data away
// print_r ($_POST) ;

	$tuple = array();
		
	$this->validator = new FormValidator();
	$this->validator->addValidation("newkey","regexp=/^[0-9a-zA-Z_-]+$/","Context name is invalid - must be [0-9a-zA-Z_-]"); 
    //Now, validate the form
    if ($this->validator->ValidateForm()) {
		
// build a cleaned input array - ignore button vectors	
		foreach ($_POST as $key=>$value) {
			if ($key == 'update_x' || $key == 'update_y' || $key == 'newkey') {
				continue;
			}
// clean it up
			if ($_POST['striptags'] == 'NO' && $key == 'extcode') {  
				$tuple[$key] = $value;
			}
			else {
				$tuple[$key] = strip_tags($value);
				$tuple[$key] = preg_replace ( "/\\\/", '', $tuple[$key]);
			}
//			$tuple[$key] = strip_tags($value);
			
		}
		if (isset($_POST['newkey'])) {
			$newkey =  trim(strip_tags($_POST['newkey']));
		}
//		print_r($tuple);

/*
 * update the SQL database
 */
		if ( $newkey != $tuple['pkey']) {			
			$ret = $this->helper->setTuple("appl",$tuple,$newkey);
		}
		else {
			$ret = $this->helper->setTuple("appl",$tuple);
		}
		
		if ($ret == 'OK') {
//			$this->helper->commitOn();	
			$this->message = " Updated ";
		}
		else {
			$this->invalidForm = True;
			$this->message = "<B>  --  Validation Errors!</B>";	
			$this->error_hash['extensave'] = $ret;	
		}			
	}
    else {
		$this->invalidForm = True;
		$this->error_hash = $this->validator->GetErrors();
		$this->message = "<B>  --  Validation Errors!</B>";		
    }
    unset ($this->validator);
}

private function xRef($pkey) {
/*
 * Build Xrefs
 */
	$xref = '';
	$tref = '';
   
	$sql = "SELECT * FROM lineio WHERE openroute LIKE '" . $pkey . "' OR closeroute LIKE '" . $pkey . "' ORDER BY pkey";
	foreach ($this->dbh->query($sql) as $row) {
		if ( $row['openroute'] == $pkey || $row['closeroute'] == $pkey ) {
                $tref .= "Trunk " . $row['pkey'] . " references this APP <br>" . PHP_EOL;
        }
	}
	if ($tref != "") {
    	$xref .= $tref;
        $tref = "";
    }
    else {
    	$xref .= "No Trunks reference this APP<br/>" . PHP_EOL;
    }  
    
 	$sql = "SELECT * FROM speed WHERE outcome LIKE '" . $pkey . "' OR out LIKE '" . $pkey . "' ORDER BY pkey";
 	foreach ($this->dbh->query($sql) as $row) {
		if ($row['pkey'] != 'RINGALL') {
			$tref .= "callgroup " . $row['pkey'] . " references this APP<br>" . PHP_EOL;
		}
	}
	
	if ($tref != "") {
    	$xref .= $tref;
        $tref = "";
    }
    else {
    	$xref .= "No callgroups reference this APP<br/>" . PHP_EOL;
    }       

	$sql = "SELECT * FROM ivrmenu where pkey != '" .$pkey . "' ORDER BY pkey";
	foreach ($this->dbh->query($sql) as $row) {
		if ($row['timeout'] == $pkey) {
			$tref .= "IVR Timeout " . $row['pkey'] . " references this APP <br>" . PHP_EOL;
		}
		else {
			for ($i = 1; $i <= 11; $i++) {
				if ($row["option" . $i] == $pkey) {
					$tref .=  "IVR " . $row['pkey'] . " references this APP<br>" . PHP_EOL;
					break 1;
				}
			}
		}
	}
	if ($tref != "") {
    	$xref .= $tref;
        $tref = "";
    }
    else {
    	$xref .= "No IVRs reference this APP<br/>" . PHP_EOL;
    }  		   		
	return $xref;
}
}
