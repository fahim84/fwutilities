<?php $this->load->view('header',$this->data); ?>
<?php $this->load->view('top_navigation',$this->data); ?>

    <script src="<?php echo base_url(); ?>assets/js/jquery.Jcrop.js"></script>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/jquery.Jcrop.css" type="text/css" />

    <!--/ Intro Single star /-->
    <section class="intro-single">

    </section>
    <!--/ Intro Single End /-->
    <section>
        <div class="container">
            <div class="row">

                <div class="col-sm-12">
                    <h1 class="title-single">Add Location URL to Image</h1>
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
                </div>
            </div>
        </div>
    </section>


    <!--/ Contact Star /-->

    <section class="contact">
        <div class="container">
            <div class="row">

                <div class="col-sm-12">

                                    <?php if($file){ ?>
                                        <div class="col-md-12 mb-3">
                                            <span>&nbsp;<a href="<?php echo base_url(); ?>image/add_location?download=<?php echo $file->modified_image; ?>" class="btn btn-primary">Download</a></span>
                                            <span>&nbsp;<a href="<?php echo base_url(); ?>image/add_location" class="btn btn-danger">Try Upload Again</a></span>
                                            <a class="btn btn-warning" href="<?php echo base_url(); ?>image/add_location/?image_id=<?php echo $file->image_id; ?>&flip=IMG_FLIP_VERTICAL">Flip Vertical</a>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <table>
                                        <?php if($file->location_url){ ?>
                                                <tr>
                                                <th>Latitude,Longitude</th>
                                                <td><?php echo $file->latitude.'<strong>,</strong>'.$file->longitude; ?></td>
                                                </tr>
                                            <tr>
                                                <th><a target="_blank" href="<?php echo $file->location_url; ?>">View on Google Map</a></th>
                                                <td><?php echo $file->location_url; ?></td>
                                            </tr>
                                        <?php }else{ ?>
                                            <tr>
                                                <th colspan="2">Location not found on image</th>
                                            </tr>
                                        <?php } ?>
                                                <tr>
                                                    <th>Size</th>
                                                    <td><?php echo $file->width.'<strong> x </strong>'.$file->height; ?></td>
                                                </tr>

                                            </table>
                                        </div>

                                    <div class="col-md-12 mb-3">
                                        <img src="<?php echo $file->modified_image_url; ?>" width="100%" >
                                    </div>

                                    <?php }else{ ?>

                                        <form class="form-a" action="<?php echo base_url(); ?>image/add_location" method="post" enctype="multipart/form-data" role="form">
                                        <div class="col-md-12 mb-3">
                                            <div class="form-group">
                                                <input name="image" type="file" id="image" accept="image/*" required class="form-control form-control-lg form-control-a">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <button type="submit" name="submit_button" value="upload" class="btn btn-a">Upload</button>
                                        </div>
                                        </form>

                                    <?php } ?>


                </div>
            </div>
        </div>
    </section>

<?php $this->load->view('footer',$this->data); ?>