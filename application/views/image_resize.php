<?php $this->load->view('header',$this->data); ?>
<?php $this->load->view('top_navigation',$this->data); ?>


    <!--/ Intro Single star /-->
    <section class="intro-single">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-lg-8">
                    <div class="title-single-box">
                        <h1 class="title-single">Upload image for resizing</h1>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!--/ Intro Single End /-->

<?php if (validation_errors()): ?>
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <?php echo validation_errors();?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['msg_error'])){ ?>
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <?php echo display_error(); ?>
    </div>
<?php } ?>

<?php if(isset($_SESSION['msg_success'])){ ?>
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <?php echo display_success_message(); ?>
    </div>
<?php } ?>

    <!--/ Contact Star /-->
    <section class="contact">
        <div class="container">
            <div class="row">

                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-a" action="<?php echo base_url(); ?>image/resize" method="post" enctype="multipart/form-data" role="form">
                                <div id="sendmessage">Your message has been sent. Thank you!</div>
                                <div id="errormessage"></div>
                                <div class="row">

                                    <?php if($file){ ?>
                                        <div class="col-md-6 mb-3">
                                            <div>Resize image to</div>

                                            <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                            <label>Target Width</label>
                                            <input type="number" step="1" name="target_width" value="<?php echo $file['width']; ?>" class="form-control form-control-lg form-control-a">
                                            </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                            <label>Target Height</label>
                                            <input type="number" name="target_height" value="<?php echo $file['height']; ?>" class="form-control form-control-lg form-control-a">
                                            </div>
                                            </div>
                                            <input type="hidden" name="image_id" value="<?php echo $file['image_id']; ?>">

                                            <div class="col-md-12">
                                                <button type="submit" name="submit_button" value="download" class="btn btn-a">Download</button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <td>Current Width</td>
                                                    <td><?php echo $file['width']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Current Height</td>
                                                    <td><?php echo $file['height']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>File Size</td>
                                                    <td><?php echo $file['file_size']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td>File Type</td>
                                                    <td><?php echo $file['file_type']; ?></td>
                                                </tr>
                                            </table>
                                            <img width="100%" src="<?php echo base_url(); ?>uploads/images/<?php echo $file['image']; ?>">
                                        </div>



                                    <?php }else{ ?>
                                        <div class="col-md-12 mb-3">
                                            <div class="form-group">
                                                <input name="image" type="file" id="image" accept="image/*" required class="form-control form-control-lg form-control-a">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <button type="submit" name="submit_button" value="upload" class="btn btn-a">Upload</button>
                                        </div>
                                    <?php } ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--/ Contact End /-->

<?php $this->load->view('footer',$this->data); ?>