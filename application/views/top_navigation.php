<!--/ Nav Star /-->
<nav class="navbar navbar-default navbar-trans navbar-expand-lg fixed-top">
    <div class="container">
        <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarDefault"
                aria-controls="navbarDefault" aria-expanded="false" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a class="navbar-brand text-brand" href="<?php echo base_url(); ?>">FW<span class="color-b">Utilities</span></a>
        <button type="button" class="btn btn-link nav-search navbar-toggle-box-collapse d-md-none" data-toggle="collapse"
                data-target="#navbarTogglerDemo01" aria-expanded="false">
            <span class="fa fa-search" aria-hidden="true"></span>
        </button>
        <div class="navbar-collapse collapse justify-content-center" id="navbarDefault">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $active == 'home' ? 'active' : ''; ?>" href="<?php echo base_url(); ?>welcome/index">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active == 'resize' ? 'active' : ''; ?>"  href="<?php echo base_url(); ?>image/resize">Photo Resizer</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active == 'crop' ? 'active' : ''; ?>"  href="<?php echo base_url(); ?>image/crop">Photo Cropper</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active == 'location' ? 'active' : ''; ?>"  href="<?php echo base_url(); ?>image/add_location">Add Location</a>
                </li>
                <!--<li class="nav-item">
                    <a class="nav-link <?php /*echo $active == 'logo' ? 'active' : ''; */?>"  href="<?php /*echo base_url(); */?>image/add_logo">Add Logo</a>
                </li>-->
                <li class="nav-item">
                    <a class="nav-link <?php echo $active == 'contact' ? 'active' : ''; ?>" href="<?php echo base_url(); ?>welcome/contact">Contact</a>
                </li>
            </ul>
        </div>

    </div>
</nav>
<!--/ Nav End /-->