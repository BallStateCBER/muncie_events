<?php
App::uses('AppController', 'Controller');
/**
 * Images Controller
 *
 * @property Image $Image
 */
class ImagesController extends AppController
{
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->deny('upload');
    }

    public function upload()
    {
        $uploadDir = ROOT.DS.APP_DIR.DS.WEBROOT_DIR.DS.'img'.DS.'events'.DS.'full'.DS;
        $fileTypes = array('jpg', 'jpeg', 'gif', 'png');
        $verifyToken = md5(Configure::read('upload_verify_token') . $_POST['timestamp']);
        if (! empty($_FILES) && $_POST['token'] == $verifyToken) {
            $tempFile = $_FILES['Filedata']['tmp_name'];
            $image_id = $this->Image->getNextId();
            $fileParts = pathinfo($_FILES['Filedata']['name']);
            $filename = $image_id.'.'.strtolower($fileParts['extension']);
            $targetFile = $uploadDir.$filename;
            if (in_array(strtolower($fileParts['extension']), $fileTypes)) {
                if ($this->Image->autoResize($tempFile)) {
                    if (move_uploaded_file($tempFile, $targetFile)) {
                        if ($this->Image->createTiny($targetFile) && $this->Image->createSmall($targetFile)) {
                            // Create DB entry for the image
                            $this->Image->create();
                            $save_result = $this->Image->save(array(
                                'filename' => $filename,
                                'user_id' => $_POST['user_id']
                            ));
                            if ($save_result) {
                                // If the event ID is available, create association
                                if (isset($_POST['event_id']) && is_int($_POST['event_id'])) {
                                    $this->Image->EventImage->create();
                                    $association_result = $this->Image->EventImage->save(array(
                                        'image_id' => $image_id,
                                        'event_id' => $_POST['event_id']
                                    ));
                                    if (! $association_result) {
                                        // error
                                    }
                                }
                                echo $this->Image->id;
                            } else {
                                $this->response->statusCode(500);
                                echo 'Error saving image';
                            }
                        } else {
                            $this->response->statusCode(500);
                            echo 'Error creating thumbnail';
                            if (! empty($this->Image->errors)) {
                                echo ': '.implode('; ', $this->Image->errors);
                            }
                        }
                    } else {
                        $this->response->statusCode(500);
                        echo 'Could not save file.';
                    }
                } else {
                    $this->response->statusCode(500);
                    echo 'Error resizing image';
                    if (! empty($this->Image->errors)) {
                        echo ': '.implode('; ', $this->Image->errors);
                    }
                }
            } else {
                echo 'Invalid file type.';
            }
        } else {
            $this->response->statusCode(500);
            echo 'Security code incorrect';
        }
        $this->layout = 'blank';
        $this->render('/Pages/blank');
    }

    /**
     * Effectively bypasses Uploadify's check for an existing file
     * (because the filename is changed as it's being saved).
     */
    public function file_exists()
    {
        exit(0);
    }

    public function newest($user_id)
    {
        $result = $this->Image->find('first', array(
            'conditions' => array('Image.user_id' => $user_id),
            'order' => 'created DESC',
            'contain' => false,
            'fields' => array('Image.id', 'Image.filename')
        ));
        if ($result) {
        } else {
            echo 0;
        }
        $this->layout = 'blank';
        $this->render('/Pages/blank');
    }

    public function filename($image_id)
    {
        $this->Image->id = $image_id;
        $filename = $this->Image->field('filename');
        echo $filename ? $filename : 0;
        $this->layout = 'blank';
        $this->render('/Pages/blank');
    }

    public function user_images($user_id)
    {
        $this->layout = 'ajax';
        $this->set(array(
            'images' => $this->Image->User->getImagesList($user_id)
        ));
    }
}
