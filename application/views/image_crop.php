<?php $this->load->view('header',$this->data); ?>
<?php $this->load->view('top_navigation',$this->data); ?>

    <script src="<?php echo base_url(); ?>assets/js/jquery.Jcrop.js"></script>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/jquery.Jcrop.css" type="text/css" />

    <!--/ Intro Single star /-->
    <section class="intro-single">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-lg-8">
                    <div class="title-single-box">
                        <h1 class="title-single">Photo Cropper</h1>
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
                    <div class="row">
                        <div class="col-md-12">
                                <div class="row">
                                    <?php if($download){ ?>
                                        <div class="col-md-12 mb-3">
                                            <span>&nbsp;<a href="<?php echo base_url(); ?>image/crop?download=<?php echo $file['image']; ?>" class="btn btn-a">Download</a></span>
                                            <span>&nbsp;<a href="<?php echo base_url(); ?>image/crop" class="btn btn-a">Try Upload Again</a></span>
                                        </div>

                                    <div class="col-md-12 mb-3">
                                        <img src="<?php echo base_url(); ?>uploads/images/<?php echo $file['image']; ?>" >
                                    </div>



                                    <?php }elseif($file){ ?>
                                    <form onsubmit="return checkCoords();" class="form-a" action="<?php echo base_url(); ?>image/crop" method="post" enctype="multipart/form-data" role="form">
                                        <div class="col-md-12 mb-3">
                                            <div class="col-md-12">
                                                <button type="submit" name="submit_button" value="crop" class="btn btn-a">Apply Crop</button>
                                            </div>
                                            <input type="hidden" name="image_id" value="<?php echo $file['image_id']; ?>">
                                            <img id="image_preview" src="<?php echo base_url(); ?>uploads/images/<?php echo $file['image']; ?>" >
                                            <input name="image" type="hidden" value="<?php echo $file['image']; ?>">
                                        </div>

                                        <input type="hidden" id="x" name="x" />
                                        <input type="hidden" id="y" name="y" />
                                        <input type="hidden" id="w" name="w" />
                                        <input type="hidden" id="h" name="h" />
                                    </form>

                                    <?php }else{ ?>
                                        <form class="form-a" action="<?php echo base_url(); ?>image/crop" method="post" enctype="multipart/form-data" role="form">
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
                </div>
            </div>
        </div>
    </section>
    <!--/ Contact End /-->

    <style type="text/css">
        #target {
            background-color: #ccc;
            width: 1100px;
            height: 1100px;
            font-size: 24px;
            display: block;
        }
    </style>
    <script>
        $(function(){

            $('#image_preview').Jcrop({
                aspectRatio: 1056/800,
                onSelect: updateCoords,
                setSelect: [0, 0, 1056, 800],// you have set proper proper x and y coordinates here
                boxWidth: 1100,
                boxHeight: 1100,
                allowSelect: false,
                allowResize: true,
                canDrag:true
            });

        });

        function updateCoords(c)
        {
            $('#x').val(c.x);
            $('#y').val(c.y);
            $('#w').val(c.w);
            $('#h').val(c.h);
        }

        function checkCoords()
        {
            if (parseInt($('#w').val())) return true;
            alert('Please select a crop region then press submit.');
            return false;
        }
    </script>
<?php $this->load->view('footer',$this->data); ?>