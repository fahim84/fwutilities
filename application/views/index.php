<?php $this->load->view('header',$this->data); ?>
<?php $this->load->view('top_navigation',$this->data); ?>


    <!--/ Intro Single star /-->
    <section class="intro-single">

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

                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6 col-lg-5">
                            <img src="<?php echo base_url(); ?>template1/img/about-1.jpg" alt="" class="img-fluid">
                        </div>

                        <div class="col-md-6 col-lg-5 section-md-t3">
                            <div class="title-box-d">
                                <h3 class="title-d">What you can do with
                                    <span class="color-d">FWUtilities</span> </h3>
                            </div>
                            <p class="color-text-a">
                                On this website you can resize an image right according to your need.
                            </p>
                            <p class="color-text-a">
                                You can upload an image and then it will ask you to provide desire width and height
                                to resize image. It will keep the aspect ratio maintained. On the final step it will give
                                you Download button to get your resized image.
                            </p>
                            <p>
                                <a class="btn btn-info" href="<?php echo base_url(); ?>/image/resize">Resize Your Image Now</a>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!--/ Contact End /-->

<?php $this->load->view('footer',$this->data); ?>