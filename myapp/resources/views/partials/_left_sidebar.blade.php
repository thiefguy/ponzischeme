<nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
            <ul class="nav" id="side-menu">
                <li class="nav-header">
                    <div class="dropdown profile-element"> <span>
                            <img alt="image" class="img-circle" src="img/profile_small.jpg" />
                             </span>
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold">David Williams</strong>
                             </span> <span class="text-muted text-xs block">Art Director <b class="caret"></b></span> </span> </a>
                        <ul class="dropdown-menu animated fadeInRight m-t-xs">
                            <li><a href="profile.html">My Profile</a></li>
                            <li><a href="contacts.html">Change Password</a></li>
                            <li><a href="{{ url('logout') }}">Logout</a></li>
                        </ul>
                    </div>
                    <div class="logo-element">
                        IN+
                    </div>
                </li>
                <li>
                    <a href="{{ url('dashboard') }}"><i class="fa fa-laptop"></i> 
                    <span class="nav-label">Dashboard</span> </a>
                </li>
                <li>
                    <a href="layouts.html"><i class="fa fa-diamond"></i> <span class="nav-label">Layouts</span> <span class="label label-primary pull-right">NEW</span></a>
                </li>
                <li>
                    <a href="#"><i class="fa fa-bar-chart-o"></i> <span class="nav-label">Provide Help</span><span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li><a href="{{ url('new/donation') }}">New Donation</a></li>
                        <li><a href="graph_flot.html">Make Payment</a></li>
                        <li><a href="graph_flot.html">Confirm Payment</a></li>
                        <li><a href="graph_flot.html">Transaction History</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#"><i class="fa fa-money"></i> <span class="nav-label">Get Help</span><span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li><a href="graph_flot.html">Request Payment</a></li>
                        <li><a href="graph_flot.html">Confirm Payment Received</a></li>
                        <li><a href="graph_flot.html">Transaction History</a></li>
                    </ul>
                </li>
        
                <li>
                    <a href="grid_options.html"><i class="fa fa-laptop"></i>
                    <span class="nav-label">View Testimonies</span></a>
                </li>
                <li>
                    <a href="grid_options.html"><i class="fa fa-laptop"></i>
                    <span class="nav-label">Annoucement</span></a>
                </li>
                 <li>
                    <a href="grid_options.html"><i class="fa fa-laptop"></i>
                    <span class="nav-label">FAQ</span></a>
                </li>
                              
                <li>
                    <a href="#"><i class="fa fa-sitemap"></i> <span class="nav-label">Menu Levels </span><span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level">
                        <li>
                            <a href="#">Third Level <span class="fa arrow"></span></a>
                            <ul class="nav nav-third-level">
                                <li>
                                    <a href="#">Third Level Item</a>
                                </li>
                                <li>
                                    <a href="#">Third Level Item</a>
                                </li>
                                <li>
                                    <a href="#">Third Level Item</a>
                                </li>

                            </ul>
                        </li>
                        <li><a href="#">Second Level Item</a></li>
                        <li>
                            <a href="#">Second Level Item</a></li>
                        <li>
                            <a href="#">Second Level Item</a></li>
                    </ul>
                </li>
                <li>
                    <a href="css_animation.html"><i class="fa fa-magic"></i> <span class="nav-label">CSS Animations </span><span class="label label-info pull-right">62</span></a>
                </li>
            </ul>

        </div>
    </nav>