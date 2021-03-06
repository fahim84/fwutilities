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
                    <h1 class="title-single">Photo Cropper</h1>
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
                                        <div class="col-md-12">
                                            <div>
                                                <button type="submit" name="submit_button" value="crop" class="btn btn-a">Apply Crop</button>
                                            </div>
                                            <input type="hidden" name="image_id" value="<?php echo $file['image_id']; ?>">

                                            <input name="image" type="hidden" value="<?php echo $file['image']; ?>">

                                            <div><input type="hidden" id="x" name="x" /></div>
                                            <div><input type="hidden" id="y" name="y" /></div>
                                            <div><input type="hidden" id="x2" name="x2" /></div>
                                            <div><input type="hidden" id="y2" name="y2" /></div>
                                            <div><input type="hidden" id="w" name="w" /></div>
                                            <div><input type="hidden" id="h" name="h" /></div>

                                            <div style="overflow: auto; border: #0c5460;">
                                                <img id="image_preview" src="<?php echo base_url(); ?>uploads/images/<?php echo $file['image']; ?>" >
                                            </div>
                                        </div>
                                    </form>

                                    <?php }else{ ?>
    <section class="contact">
        <div class="container">
            <div class="row">

                <div class="col-sm-12">
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
                </div>
            </div>
        </div>
    </section>
                                    <?php } ?>




    <style type="text/css">
        #target {
            background-color: #ccc;
            width: 1100px;
            height: 1100px;
            font-size: 24px;
            display: block;
        }
        /*.container{
            margin: 0;
            padding: 0;
        }*/
    </style>
    <script>
        $(function(){

            var box_width = $('#image_preview').width();
            $('#image_preview').Jcrop({
                //aspectRatio: 1056/800,
                //aspectRatio: 0,
                onSelect: updateCoords,
                //setSelect: [0, 0, 1056, 800],// you have set proper proper x and y coordinates here
                //boxWidth: 1100,
                //boxHeight: 1100,
                //allowSelect: true,
                //allowResize: true,
                //canDrag:true,
                boxWidth: box_width
            });

        });

        function updateCoords(c)
        {
            $('#x').val(c.x);
            $('#y').val(c.y);
            $('#x2').val(c.x2);
            $('#y2').val(c.y2);
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