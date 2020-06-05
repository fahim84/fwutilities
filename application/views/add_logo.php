<?php $this->load->view('header',$this->data); ?>
<?php $this->load->view('top_navigation',$this->data); ?>

    <link rel="StyleSheet" href="<?php echo base_url(); ?>assets/watermarkimage/watermarker.css" type="text/css">
    <script type="text/javascript" src="<?php echo base_url(); ?>assets/watermarkimage/watermarker.js"></script>

    <!--/ Intro Single star /-->
    <section class="intro-single">

    </section>
    <!--/ Intro Single End /-->
    <section>
        <div class="container">
            <div class="row">

                <div class="col-sm-12">
                    <!--<h1 class="title-single">Add Logo to Image</h1>-->
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
                    <form class="form-a" action="<?php echo base_url(); ?>image/add_logo/?image_id=<?php echo $file->image_id; ?>" method="post" enctype="multipart/form-data" role="form">
                                        <div class="col-md-12 mb-3">
                                            <span>&nbsp;<a href="<?php echo base_url(); ?>image/add_logo" class="btn btn-danger">Try Upload Again</a></span>
                                            <a class="btn btn-warning" href="<?php echo base_url(); ?>image/add_logo/?image_id=<?php echo $file->image_id; ?>&flip=IMG_FLIP_VERTICAL">Flip Vertical</a>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <table>
                                        <?php if($file->location_url){ ?>
                                                <tr>
                                                <th>Latitude,Longitude</th>
                                                <td><?php echo $file->latitude.'<strong>,</strong>'.$file->longitude; ?></td>
                                                </tr>
                                            <tr>
                                                <th><a target="_blank" class="btn btn-info" href="<?php echo $file->location_url; ?>">View on Google Map</a></th>
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

                                        <input type="hidden" name="image_id" value="<?php echo $file->image_id; ?>">
                                        <input type="hidden" id="posx" name="x">
                                        <input type="hidden" id="posy" name="y">
                                        <input type="hidden" id="width" name="width">
                                        <input type="hidden" id="height" name="height">
                                        <input type="hidden" id="image_container_width" name="image_container_width">
                                        <input type="hidden" id="image_container_height" name="image_container_height">

                                        <input type="hidden" id="opacity">
                                        <input type="hidden" id="mousex">
                                        <input type="hidden" id="mousey">
                        <div class="col-md-12 mb-3">
                        <button type="submit" name="submit_button" value="upload" class="btn btn-primary">Download</button>
                        </div>
                    </form>

                                    <div class="col-md-12 mb-3">
                                        <img id="image" src="<?php echo $file->modified_image_url; ?>?r=<?php echo time(); ?>" width="100%" >
                                    </div>


                                        <form class="form-a" action="<?php echo base_url(); ?>image/add_logo/?image_id=<?php echo $file->image_id; ?>" method="post" enctype="multipart/form-data" role="form">

                                            <?php if($file->image2){ ?>
                                            <div class="col-md-12 mb-3">
                                                <img src="<?php echo $file->image2_url; ?>" width="200" >
                                            </div>
                                            <?php } ?>

                                            <div class="col-md-12 mb-3">
                                                <h1>Upload Logo</h1>
                                                <div class="form-group">
                                                    <input name="image2" type="file" id="image2" accept="image/*" required class="form-control form-control-lg form-control-a">
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <button type="submit" name="submit_button" value="upload" class="btn btn-a">Upload Logo</button>
                                            </div>
                                        </form>

                                    <?php }else{ ?>

                                        <form class="form-a" action="<?php echo base_url(); ?>image/add_logo" method="post" enctype="multipart/form-data" role="form">
                                        <div class="col-md-12 mb-3">
                                            <h1>Upload Image</h1>
                                            <div class="form-group">
                                                <input name="image" type="file" id="image" accept="image/*" required class="form-control form-control-lg form-control-a">
                                            </div>
                                        </div>

                                            <div class="col-md-12 mb-3">
                                                <h1>Upload Logo</h1>
                                                <div class="form-group">
                                                    <input name="image2" type="file" id="image2" accept="image/*" required class="form-control form-control-lg form-control-a">
                                                </div>
                                            </div>

                                        <div class="col-md-12">
                                            <button type="submit" name="submit_button" value="upload" class="btn btn-a">Upload Image</button>
                                        </div>
                                        </form>

                                    <?php } ?>


                </div>
            </div>
        </div>
    </section>

<script>

    <?php if($file){ ?>

    image_container_width = $("#image").width();
    image_container_height = $("#image").height();

    $('#image_container_width').val(image_container_width);
    $('#image_container_height').val(image_container_height);
    $(document).on("change",'#image',function(event){
        image_container_width = $("#image").width();
        image_container_height = $("#image").height();

        $('#image_container_width').val(image_container_width);
        $('#image_container_height').val(image_container_height);
    });

    (function(){

        function updateCoords (coords){
            $("#posx").val(coords.x);
            $("#posy").val(coords.y);
            $("#width").val(coords.width);
            $("#height").val(coords.height);
            $("#opacity").val(coords.opacity);
        }

        $("#image").watermarker({
            imagePath: "<?php echo $file->image2_url; ?>",
            removeIconPath: "<?php echo base_url(); ?>assets/watermarkimage/images/close-icon.png",
            offsetLeft:30,
            offsetTop: 40,
            onChange: updateCoords,
            onInitialize: updateCoords,
            containerClass: "myContainer",
            watermarkImageClass: "myImage superImage",
            watermarkerClass: "js-watermark-1 js-watermark",
            data: {id: 1, "class": "superclass", pepe: "pepe"},
            onRemove: function(){
                if(typeof console !== "undefined" && typeof console.log !== "undefined"){
                    console.log("Removing...");
                }
            },
            onDestroy: function(){
                if(typeof console !== "undefined" && typeof console.log !== "undefined"){
                    console.log("Destroying...");
                }
            }
        });


        $(document).on("mousemove",function(event){
            $("#mousex").val(event.pageX);
            $("#mousey").val(event.pageY);
        });

    })();

    <?php } ?>
</script>

<?php $this->load->view('footer',$this->data); ?>