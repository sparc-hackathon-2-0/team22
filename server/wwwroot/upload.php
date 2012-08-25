<?php 

// filename: upload.php 

$sql = mysqli_init() or error("Unable to init mysql");

$sql->real_connect() or error("Unable to connect to mysql");

$_POST or error("No POST");
$username = $sql->real_escape_string($_POST["username"]) or error("need a username");
$question = $sql->real_escape_string($_POST["question"]) or error("need a question");


// make a note of the current working directory, relative to root. 
$directory_self = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']); 

// make a note of the directory that will recieve the uploaded file 
$uploadsDirectory = $_SERVER['DOCUMENT_ROOT'] . $directory_self . 'images/'; 

// make a note of the location of the upload form in case we need it 
$uploadForm = 'http://' . $_SERVER['HTTP_HOST'] . $directory_self . 'upload.form.php'; 

// make a note of the location of the success page 
$uploadSuccess = 'http://' . $_SERVER['HTTP_HOST'] . $directory_self . 'upload.success.php'; 

// fieldname used within the file <input> of the HTML form 
$fieldname = 'file'; 

// Now let's deal with the upload 

// possible PHP upload errors 
$errors = array(1 => 'php.ini max file size exceeded', 
                2 => 'html form max file size exceeded', 
                3 => 'file upload was only partial', 
                4 => 'no file was attached'); 

/*
// check the upload form was actually submitted else print the form 
isset($_POST['submit']) 
    or error('the upload form is neaded', $uploadForm); 
*/

// check for PHP's built-in uploading errors
($_FILES) or error("No files uploaded");
($_FILES[$fieldname]) or error("No file field in files"); 
($_FILES[$fieldname]['error'] == 0) 
    or error($errors[$_FILES[$fieldname]['error']]); 
     
// check that the file we are working on really was the subject of an HTTP upload 
@is_uploaded_file($_FILES[$fieldname]['tmp_name']) 
    or error('not an HTTP upload'); 
     
// validation... since this is an image upload script we should run a check   
// to make sure the uploaded file is in fact an image. Here is a simple check: 
// getimagesize() returns false if the file tested is not an image. 
@getimagesize($_FILES[$fieldname]['tmp_name']) 
    or error('only image uploads are allowed'); 
     
// make a unique filename for the uploaded file and check it is not already 
// taken... if it is already taken keep trying until we find a vacant one 
// sample filename: 1140732936-filename.jpg 
$now = time(); 
$uploadFilename = $now.'-'.$_FILES[$fieldname]['name'];
while(file_exists($uploadFilename = $uploadsDirectory.$uploadFilename)) 
{ 
    $now++; 
	$uploadFilename = $now.'-'.$_FILES[$fieldname]['name'];
} 

// now let's move the file to its final location and allocate the new filename to it 
@move_uploaded_file($_FILES[$fieldname]['tmp_name'], $uploadsDirectory.$uploadFilename) 
    or error('receiving directory insuffiecient permission');
   

$result = $sql->query("SELECT id FROM users WHERE name=".$username) or error("cant find userid");
($result->num_rows == 1) or error("cant find userid");

$row = $result->fetch_row();
$userid = $row['id'];

$sql->query("INSERT INTO pictures (user_id, rel_path) VALUES ($userid, $uploadFilename)")
	or error("cant insert into pictures");
	
$id = $sql->insert_id or error("File uploaded, but no id in database");

$message = '{ '.
	"\"result\":$id".
'}';

echo $message;
 



/*   
// If you got this far, everything has worked and the file has been successfully saved. 
// We are now going to redirect the client to a success page. 
header('Location: ' . $uploadSuccess); 
*/

// The following function is an error handler which is used 
// to output an HTML error page if the file upload fails 
function error($error, $seconds = 5) 
{ 
    echo '{'.
	"\"error\": \"$error\"".
	'}';
	
	if(isset($uploadFilename, $uploadsDirectory))
	{
		unlink($uploadsDirectory.$uploadFilename);
	}
    exit; 
} // end error handler 

?>