﻿using System;
using System.Collections.Generic;
using System.Runtime.ConstrainedExecution;
using System.Text;
using System.Dynamic;

namespace SahabatSurabaya
{
    class Session
    {
       
        public static User userLogin { get; set; }
        public static ReportDetailPageParams reportDetailPageParam { get; set; }
        public static CrimeReportParams crimeReportParam { get; set; }
        public static LostFoundReportParams lostFoundReportParam { get; set; }

        public static ChatPageParams chatPageParam { get; set; }
        public readonly static string API_URL = "http://adrian-webservice.ta-istts.com/";
        public readonly static string URL_WEBVIEW = "http://adrian-webview.ta-istts.com/";

        public ChatPageParams getChatPageParams()
        {
            return chatPageParam;
        }

        public void setChatPageParams(ChatPageParams param)
        {
            chatPageParam = param;
        }

        public LostFoundReportParams getLostFoundReportParams()
        {
            return lostFoundReportParam;
        }

        public void setLostFoundReportDetailPageParams(LostFoundReportParams param)
        {
            lostFoundReportParam = param;
        }
        public void setCrimeReportDetailPageParams(CrimeReportParams param)
        {
            crimeReportParam = param;
        }

        public CrimeReportParams getCrimeReportParams()
        {
            return crimeReportParam;
        }

        public void setReportDetailPageParams(ReportDetailPageParams param)
        {
            reportDetailPageParam = param;
        }

        public ReportDetailPageParams getReportDetailPageParams()
        {
            return reportDetailPageParam;
        }


        public void setUserLogin (User user)
        {
            userLogin = user;
        }

        public User getUserLogin()
        {
            return userLogin;
        }

        public string getApiURL()
        {
            return API_URL;
        }
        
        public string getUrlWebView()
        {
            return URL_WEBVIEW;
        }
    }
}