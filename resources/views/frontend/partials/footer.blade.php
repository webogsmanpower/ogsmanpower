<!-- OGS FOOTER START -->
<footer class="ogs-footer">

    <style>
    .ogs-footer {
        background: #2b2f3a;
        color: #cbd5e1;
        padding: 40px 0;
        font-size: 14px;
    }

    /* TOP LINKS */
    .footer-top {
        text-align: center;
        margin-bottom: 20px;
    }

    .footer-top a {
        color: #cbd5e1;
        text-decoration: none;
        margin: 0 10px;
    }

    .footer-top span {
        color: #64748b;
    }

    /* HEADINGS */
    .ogs-footer h5 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 15px;
    }

    /* LIST */
    .ogs-footer ul {
        list-style: none;
        padding: 0;
    }

    .ogs-footer ul li {
        margin-bottom: 8px;
        padding-left: 12px;
        position: relative;
    }

    .ogs-footer ul li::before {
        content: "▪";
        color: #38bdf8;
        position: absolute;
        left: 0;
    }

    /* GRID */
    .ogs-footer .row {
        margin-top: 20px;
    }

    /* DIVIDER */
    .ogs-footer hr {
        border-color: #3b4252;
        margin: 20px 0;
    }

    /* BOTTOM */
    .footer-bottom {
        border-top: 1px solid #3b4252;
        margin-top: 20px;
        padding-top: 15px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        font-size: 13px;
    }

    .footer-bottom p {
        margin: 0;
    }
    /* MAKE FOOTER RELATIVE */
.ogs-footer {
    position: relative;
}

/* LOGO POSITION */
.footer-logo-fixed {
    position: absolute;
    right: 20px;
    top: -40px; /* moves logo slightly above footer */
    z-index: 10;
}

/* IMAGE STYLE */
.footer-logo-fixed img {
    width: 100px;
    height: auto;
    border-radius: 50%;
    background: #fff;
    padding: 5px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}

    /* MOBILE FIX */
    @media (max-width: 768px) {
        .footer-bottom {
            text-align: center;
            justify-content: center;
            gap: 10px;
        }
    }
    </style>

    <div class="container">

        <!-- TOP LINKS -->
        <div class="footer-top">
            <a href="#">Faq</a>
            <span>|</span>
            <a href="#">Privacy</a>
            <span>|</span>
            <a href="#">Downloads</a>
            <span>|</span>
            <a href="#">Gallery</a>
            <span>|</span>
            <a href="#">Contact Us</a>
            <span>|</span>
            <a href="#">OGS Member List</a>
        </div>
<!-- FLOATING OGS LOGO -->
<div class="ogs-floating-logo" style="float: right;width: 140px;margin-top: -140px;">
    <img src="{{ asset('../icons/15yearslogo.png') }}" alt="OGS Logo">
</div>
        <hr>

        <!-- MAIN GRID -->
        <div class="row">

            <div class="col-lg-3 col-md-6">
                <h5>Jobs By Industry</h5>
                <ul>
                    <li>Drilling Oil & Gas</li>
                    <li>Information Technology</li>
                    <li>Accounts, Finance and Business</li>
                    <li>Engineering & Technology</li>
                    <li>Construction Consultants</li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>Jobs By Location</h5>
                <ul>
                    <li>Pakistan</li>
                    <li>Saudi Arabia</li>
                    <li>Australia</li>
                    <li>USA</li>
                    <li>UK</li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>Job Seekers</h5>
                <ul>
                    <li>Search job</li>
                    <li>Create Account</li>
                    <li>View Cv</li>
                    <li>Recent Jobs</li>
                    <li>Job alerts</li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>Employers</h5>
                <ul>
                    <li>Free Register Company</li>
                    <li>Post Job</li>
                    <li>Search Resume</li>
                    <li>Business Options</li>
                    <li>Put Your Ad</li>
                </ul>
            </div>

        </div>

        <!-- BOTTOM -->
        <div class="footer-bottom">
            <p>Copyright © 2012 OGSmanpower.com. All rights reserved.</p>
            <p>Powered By: <span style="color:#38bdf8;">OGSmanpower</span></p>
        </div>

    </div>

</footer>
<!-- OGS FOOTER END -->