<?php $this->load->view('header',$this->data); ?>
<?php $this->load->view('top_navigation',$this->data); ?>

    <!--/ Intro Single star /-->
    <section class="intro-single">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-lg-8">
                    <div class="title-single-box">
                        <h1 class="title-single">Contact US</h1>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--/ Intro Single End /-->

    <!--/ Contact Star /-->
    <section class="contact">
        <div class="container">
            <div class="row">

                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-md-7">
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
                            <form class="form-a" action="" method="post" role="form">
                                <div id="sendmessage">Your message has been sent. Thank you!</div>
                                <div id="errormessage"></div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <input type="text" name="name" class="form-control form-control-lg form-control-a" placeholder="Your Name" data-rule="minlen:4" data-msg="Please enter at least 4 chars" required>
                                            <div class="validation"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <input name="email" type="email" class="form-control form-control-lg form-control-a" placeholder="Your Email" data-rule="email" data-msg="Please enter a valid email" required>
                                            <div class="validation"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-group">
                                            <input type="text" name="subject" class="form-control form-control-lg form-control-a" placeholder="Subject" data-rule="minlen:4" data-msg="Please enter at least 8 chars of subject" required>
                                            <div class="validation"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-group">
                                            <textarea name="message" class="form-control" name="message" cols="45" rows="8" data-rule="required" data-msg="Please write something for us" placeholder="Message" required></textarea>
                                            <div class="validation"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-a">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-5 section-md-t3">
                            <div class="icon-box section-b2">
                                <div class="icon-box-icon">
                                    <span class="ion-ios-paper-plane"></span>
                                </div>
                                <div class="icon-box-content table-cell">
                                    <div class="icon-box-title">
                                        <h4 class="icon-title">Say Hello</h4>
                                    </div>
                                    <div class="icon-box-content">
                                        <p class="mb-1">Email.
                                            <span class="color-a"><?php echo SUPPORT_EMAIL; ?></span>
                                        </p>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--/ Contact End /-->

<?php $this->load->view('footer',$this->data); ?>