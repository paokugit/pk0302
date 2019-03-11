<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Shop_EweiShopV2Page extends AppMobilePage
{
    public function ce(){
       
            var_dump(base64_decode("eyJwYWdlIjp7InR5cGUiOiIyMCIsInRpdGxlIjoiXHU1NTQ2XHU1N2NlIiwibmFtZSI6Ilx1NTU0Nlx1NTdjZSIsImRlc2MiOiJcdTU1NDZcdTU3Y2UiLCJpY29uIjoiIiwiYmFja2dyb3VuZCI6IiNmM2YzZjMiLCJ0aXRsZWJhcmJnIjoiI2ZmZmZmZiIsInRpdGxlYmFyY29sb3IiOiIjMDAwMDAwIn0sIml0ZW1zIjp7Ik0xNTQ3MTgyODgxNDYxIjp7InN0eWxlIjp7ImRvdHN0eWxlIjoicm91bmQiLCJkb3RhbGlnbiI6ImNlbnRlciIsImJhY2tncm91bmQiOiIjZmZmZmZmIiwib3BhY2l0eSI6IjAuOCJ9LCJkYXRhIjp7Ik0xNTQ3MjY3MzAyNjc0Ijp7ImltZ3VybCI6ImltYWdlcy8xLzIwMTkvMDEvYmhkMTEya2dHdmc1eWM2Nkg4ZHlHM1RoeTZsdnlWLmpwZyIsImxpbmt1cmwiOiIifSwiTTE1NDc4MDQyOTc3NzAiOnsiaW1ndXJsIjoiaW1hZ2VzLzEvMjAxOS8wMS9nMWRvOHM3ZDc3THo3N2RoMUZGTEhMNzJ6RzlGcDcuanBnIiwibGlua3VybCI6IiJ9LCJNMTU0NzgwNDI5ODIzMiI6eyJpbWd1cmwiOiJpbWFnZXMvMS8yMDE5LzAxL094bUt2YUFSUkdSQjFNWk1Ia20xNzdWS01YWk9tby5qcGciLCJsaW5rdXJsIjoiIn19LCJpZCI6ImJhbm5lciJ9LCJNMTU0NzExNTc4NTA5MSI6eyJwYXJhbXMiOnsicGxhY2Vob2xkZXIiOiJcdThiZjdcdThmOTNcdTUxNjVcdTUxNzNcdTk1MmVcdTViNTdcdThmZGJcdTg4NGNcdTY0MWNcdTdkMjIifSwic3R5bGUiOnsiaW5wdXRiYWNrZ3JvdW5kIjoiI2ZmZmZmZiIsImJhY2tncm91bmQiOiIjZjFmMWYyIiwiaWNvbmNvbG9yIjoiI2I0YjRiNCIsImNvbG9yIjoiIzk5OTk5OSIsInBhZGRpbmd0b3AiOiIxMCIsInBhZGRpbmdsZWZ0IjoiMTAiLCJ0ZXh0YWxpZ24iOiJsZWZ0Iiwic2VhcmNoc3R5bGUiOiJyb3VuZCJ9LCJpZCI6InNlYXJjaCJ9LCJNMTU0NzgwNDMxMjc0OCI6eyJzdHlsZSI6eyJuYXZzdHlsZSI6IiIsImJhY2tncm91bmQiOiIjZmZmZmZmIiwicm93bnVtIjoiNSIsInNob3d0eXBlIjoiMCIsInBhZ2VudW0iOiI4Iiwic2hvd2RvdCI6IjEifSwiZGF0YSI6eyJDMTU0NzgwNDMxMjc0OCI6eyJpbWd1cmwiOiJpbWFnZXMvMS8yMDE5LzAyL1ZENkE1ckozUlJSRzV6ZHJRYTUxVmFPcDAwakg4ci5wbmciLCJsaW5rdXJsIjoiXC9wYWdlc1wvc2hvcFwvY2FyZWdvcnlcL2luZGV4IiwidGV4dCI6Ilx1NTE2OFx1OTBlOFx1NTIwNlx1N2M3YiIsImNvbG9yIjoiIzY2NjY2NiJ9LCJDMTU0NzgwNDMxMjc1MCI6eyJpbWd1cmwiOiJpbWFnZXMvMS8yMDE5LzAyL3E5UTk0NGo0MTVjQ3RDQzU5MGNjMTQ0WUNDQzVDNS5wbmciLCJsaW5rdXJsIjoiXC9wYWdlc1wvbWVtYmVyXC9jYXJ0XC9pbmRleCIsInRleHQiOiJcdThkMmRcdTcyNjlcdThmNjYiLCJjb2xvciI6IiM2NjY2NjYifSwiQzE1NDc4MDQzMTI3NDkiOnsiaW1ndXJsIjoiaW1hZ2VzLzEvMjAxOS8wMi93S2tOcDVONTVmdnU5NTVVZFpmUms5VThrbjU4OXYucG5nIiwibGlua3VybCI6IlwvcGFnZXNcL2NvbW1pc3Npb25cL2luZGV4IiwidGV4dCI6Ilx1NjNhOFx1NWU3Zlx1NGUyZFx1NWZjMyIsImNvbG9yIjoiIzY2NjY2NiJ9LCJDMTU0NzgwNDMxMjc1MSI6eyJpbWd1cmwiOiJpbWFnZXMvMS8yMDE5LzAyL3A3N1BGeDAwVWdwQWVlbEZrR3VyWDdmc3pTZW9Bay5wbmciLCJsaW5rdXJsIjoiXC9wYWdlc1wvb3JkZXJcL2luZGV4IiwidGV4dCI6Ilx1NjIxMVx1NzY4NFx1OGJhMlx1NTM1NSIsImNvbG9yIjoiIzY2NjY2NiJ9LCJNMTU0NzgwNDM1NTU3NiI6eyJpbWd1cmwiOiJpbWFnZXMvMS8yMDE5LzAyL0NFU2Nxc2pjbWNzU3NVeXBxaUE2QUNlZlN6U3NzdS5wbmciLCJsaW5rdXJsIjoiXC9wYWdlc1wvZ29vZHNcL2RldGFpbFwvaW5kZXg/aWQ9NyIsInRleHQiOiJcdTYyMTBcdTRlM2FcdTVlOTdcdTRlM2IiLCJjb2xvciI6IiM2NjY2NjYifX0sImlkIjoibWVudSJ9LCJNMTU0NzgwNDM3ODMyMiI6eyJwYXJhbXMiOnsicm93IjoiMSIsInNob3d0eXBlIjoiMCIsInBhZ2VudW0iOiIyIn0sInN0eWxlIjp7ImJhY2tncm91bmQiOiIjZjJmMmYyIiwicGFkZGluZ3RvcCI6IjMiLCJwYWRkaW5nbGVmdCI6IjMiLCJzaG93ZG90IjoiMCIsInNob3didG4iOiIwIn0sImRhdGEiOnsiQzE1NDc4MDQzNzgzMjIiOnsiaW1ndXJsIjoiaW1hZ2VzLzEvMjAxOS8wMi9NbnVuM2Fsak9tbWw2Wm1NTW9KMXp2YUszNlUzb1UucG5nIiwibGlua3VybCI6IlwvcGFnZXNcL2dvb2RzXC9pbmRleFwvaW5kZXg/Y2F0ZT02MCJ9LCJDMTU0NzgwNDM3ODMyMyI6eyJpbWd1cmwiOiJpbWFnZXMvMS8yMDE5LzAyL0ZZcDAyajM4ODNLanMzNmJzOHZaN2pLWTQ2RUs4Uy5wbmciLCJsaW5rdXJsIjoiXC9wYWdlc1wvZ29vZHNcL2luZGV4XC9pbmRleD9jYXRlPTU3In0sIkMxNTQ3ODA0Mzc4MzI0Ijp7ImltZ3VybCI6ImltYWdlcy8xLzIwMTkvMDIvSlpmRGF4RnBYZkZsM1pBWEtWbExwRFh4OFNMMmxHLnBuZyIsImxpbmt1cmwiOiJcL3BhZ2VzXC9nb29kc1wvaW5kZXhcL2luZGV4P2NhdGU9NTkifX0sImlkIjoicGljdHVyZXcifSwiTTE1NDcyNjc2NDc3MTIiOnsicGFyYW1zIjp7InRpdGxlIjoiLS1cdTcyMDZcdTZiM2VcdTU1NDZcdTU0YzEtLSIsImljb24iOiIifSwic3R5bGUiOnsiYmFja2dyb3VuZCI6IiNmZmZmZmYiLCJjb2xvciI6IiMwMDAwMDAiLCJ0ZXh0YWxpZ24iOiJjZW50ZXIiLCJmb250c2l6ZSI6IjE3IiwicGFkZGluZ3RvcCI6IjEyIiwicGFkZGluZ2xlZnQiOiI1In0sImlkIjoidGl0bGUifSwiTTE1NDcxMTU3OTEwNDMiOnsicGFyYW1zIjp7InNob3d0aXRsZSI6IjEiLCJzaG93cHJpY2UiOiIxIiwiZ29vZHNkYXRhIjoiMCIsImNhdGVpZCI6IjYiLCJjYXRlbmFtZSI6Ilx1NjI0Ylx1NjczYSIsImdyb3VwaWQiOiIiLCJncm91cG5hbWUiOiIiLCJnb29kc3NvcnQiOiIwIiwiZ29vZHNzY3JvbGwiOiIxIiwiZ29vZHNudW0iOiIxMCIsInNob3dpY29uIjoiMCIsImljb25wb3NpdGlvbiI6ImxlZnQgdG9wIiwicHJvZHVjdHByaWNlIjoiMSIsInNob3dwcm9kdWN0cHJpY2UiOiIwIiwic2hvd3NhbGVzIjoiMCIsInByb2R1Y3RwcmljZXRleHQiOiJcdTUzOWZcdTRlZjciLCJzYWxlc3RleHQiOiJcdTk1MDBcdTkxY2YiLCJwcm9kdWN0cHJpY2VsaW5lIjoiMCIsInNhbGVvdXQiOiIwIiwic2VlY29tbWlzc2lvbiI6IjAiLCJjYW5zZWUiOiIwIiwic2VldGl0bGUiOiIiLCJwYWdldHlwZSI6IjIwIn0sInN0eWxlIjp7ImJhY2tncm91bmQiOiIjZjNmM2YzIiwibGlzdHN0eWxlIjoiYmxvY2sgdGhyZWUiLCJidXlzdHlsZSI6ImJ1eWJ0bi0xIiwiZ29vZHNpY29uIjoicmVjb21tYW5kIiwiaWNvbnN0eWxlIjoidHJpYW5nbGUiLCJwcmljZWNvbG9yIjoiI2ZmNTU1NSIsInByb2R1Y3RwcmljZWNvbG9yIjoiIzk5OTk5OSIsImljb25wYWRkaW5ndG9wIjoiMCIsImljb25wYWRkaW5nbGVmdCI6IjAiLCJidXlidG5jb2xvciI6IiNmZjU1NTUiLCJpY29uem9vbSI6IjEwMCIsInRpdGxlY29sb3IiOiIjMDAwMDAwIiwidGFnYmFja2dyb3VuZCI6IiNmZTU0NTUiLCJzYWxlc2NvbG9yIjoiIzk5OTk5OSJ9LCJkYXRhIjp7IkMxNTQ3MTE1NzkxMDQzIjp7ImdpZCI6IjI1IiwiZGVkdWN0IjoiMzAuMDAiLCJ0aXRsZSI6IkNIQVJMRVNcdWZmMDZLRUlUSFx1NzljYlx1NTFhY1x1ODg5Y1x1OTc3NFx1NTk3M0NLMS05MDkyMDA0OVx1NmIyN1x1N2Y4ZVx1OThjZVx1NTcwNlx1NTkzNFx1N2VjNlx1OWFkOFx1OGRkZlx1NTNjYVx1OGUxZFx1OTc3NCIsInN1YnRpdGxlIjoiIiwicHJpY2UiOiI0OTkuMDAiLCJ0aHVtYiI6ImltYWdlc1wvMVwvMjAxOVwvMDFcL00yS1Q0cjJiTEtLMjBCWVBQVzBQUDByQlIyQzBacjIyLmpwZyIsInRvdGFsIjoiOSIsInByb2R1Y3RwcmljZSI6IjAuMDAiLCJjdHlwZSI6IjEiLCJzYWxlcyI6IjAiLCJ2aWRlbyI6IiIsInNlZWNvbW1pc3Npb24iOiIwIiwiY2Fuc2VlIjoiMCIsInNlZXRpdGxlIjoiIiwiYmFyZ2FpbiI6IjAifSwiQzE1NDcxMTU3OTEwNDQiOnsiZ2lkIjoiMjgiLCJkZWR1Y3QiOiIwLjAwIiwidGl0bGUiOiJcdTVjMGZcdTg2NmIzNy41XHU2MjgwXHU2NzJmXHU5NzY5XHU2NWIwXHU2ZTI5XHU2Njk2XHU0ZjUzXHU5YThjXHU2MmJkXHU3ZWYzXHU4ZmRlXHU1ZTNkXHU5NjMyXHU5OGNlXHU3N2VkXHU2YjNlXHU1OTE2XHU1OTU3XHU1OTczMjAxOFx1NWU3NFx1NjViMFx1NmIzZVx1NTFhY1x1NWI2MyIsInN1YnRpdGxlIjoiIiwicHJpY2UiOiI5ODAuMDAiLCJ0aHVtYiI6ImltYWdlc1wvMVwvMjAxOVwvMDFcL0szOXpkUmRSVmV6dlpudnJuUlZwMWVEWWVWeDFOcDRVLmpwZyIsInRvdGFsIjoiODAiLCJwcm9kdWN0cHJpY2UiOiIxMDUwLjAwIiwiY3R5cGUiOiIxIiwic2FsZXMiOiIwIiwidmlkZW8iOiIiLCJzZWVjb21taXNzaW9uIjoiMCIsImNhbnNlZSI6IjAiLCJzZWV0aXRsZSI6IiIsImJhcmdhaW4iOiIwIn0sIkMxNTQ3MTE1NzkxMDQ1Ijp7ImdpZCI6IjMxIiwiZGVkdWN0IjoiMC4wMCIsInRpdGxlIjoiVmVybyBNb2RhMjAxOFx1NTkwZlx1NWI2M1x1NjViMFx1NmIzZVx1NGUwMFx1NWI1N1x1OTg4Nlx1NmUyOVx1NjdkNFx1OThjZVx1NTQwYVx1NWUyNlx1NTQwZFx1NWE5Ylx1OGZkZVx1ODg2M1x1ODhkOVx1NTk3M3wzMTgxN0I1MDgiLCJzdWJ0aXRsZSI6IiIsInByaWNlIjoiNTc5LjAwIiwidGh1bWIiOiJpbWFnZXNcLzFcLzIwMTlcLzAxXC9qSDR5eDlpSDk0OUd2MXhpZ1o5WHY3NHoyMllwN1RIdC5qcGciLCJ0b3RhbCI6IjEwIiwicHJvZHVjdHByaWNlIjoiMC4wMCIsImN0eXBlIjoiMSIsInNhbGVzIjoiMCIsInZpZGVvIjoiIiwic2VlY29tbWlzc2lvbiI6IjAiLCJjYW5zZWUiOiIwIiwic2VldGl0bGUiOiIiLCJiYXJnYWluIjoiMCJ9LCJDMTU0NzExNTc5MTA0NiI6eyJnaWQiOiIzNCIsImRlZHVjdCI6IjAuMDAiLCJ0aXRsZSI6Ilx1MzAxMFx1NjViMFx1NmIzZVx1NTFjZjEwXHUzMDExXHU4NTdlXHU0ZTFkXHU2MmZjXHU2M2E1XHU4OGU0XHU4OGQ5XHU1OTczMjAxOVx1NjYyNVx1NTkwZlx1OWFkOFx1NWYzOWluc1x1OGQ4NVx1NzA2Ylx1OWFkOFx1ODE3MFx1NzI1Ylx1NGVkNFx1NTM0YVx1OGVhYlx1ODhkOSIsInN1YnRpdGxlIjoiIiwicHJpY2UiOiI4OS4wMCIsInRodW1iIjoiaW1hZ2VzXC8xXC8yMDE5XC8wMVwvVTZZZFk4djFVODY3MTI1NTVZbDh5RDYwMjJZTFpZMjIuanBnIiwidG90YWwiOiIxMCIsInByb2R1Y3RwcmljZSI6IjAuMDAiLCJjdHlwZSI6IjEiLCJzYWxlcyI6IjAiLCJ2aWRlbyI6IiIsInNlZWNvbW1pc3Npb24iOiIwIiwiY2Fuc2VlIjoiMCIsInNlZXRpdGxlIjoiIiwiYmFyZ2FpbiI6IjAifSwiTTE1NTExNjg5NjIyOTIiOnsidGl0bGUiOiJsaWhhbndlbl9cdTZkNGJcdThiZDVcdTViOWVcdTRmNTNcdTU1NDZcdTU0YzEiLCJ0aHVtYiI6Imh0dHA6XC9cL3Bhb2t1LnhpbmdydW5zaGlkYWkuY29tXC9hdHRhY2htZW50XC9pbWFnZXNcLzFcLzIwMTlcLzAyXC9IdUk5RjFGN3RXMnZWUXRwVTBkMTkwSzREMXo5NFUuanBnIiwicHJpY2UiOiIwLjAxIiwiZ2lkIjoiMTM0IiwiYmFyZ2FpbiI6IjAifX0sImlkIjoiZ29vZHMifSwiTTE1NDc4MDQ0NTExODYiOnsicGFyYW1zIjp7ImhpZGV0ZXh0IjoiMCIsInNob3d0eXBlIjoiMCIsInJvd251bSI6IjEiLCJzaG93YnRuIjoiMCJ9LCJzdHlsZSI6eyJiYWNrZ3JvdW5kIjoiI2ZmZmZmZiIsInBhZGRpbmd0b3AiOiI0IiwicGFkZGluZ2xlZnQiOiI1IiwidGl0bGVhbGlnbiI6ImNlbnRlciIsInRleHRhbGlnbiI6ImNlbnRlciIsInRpdGxlY29sb3IiOiIjZmZmZmZmIiwidGV4dGNvbG9yIjoiIzY2NjY2NiJ9LCJkYXRhIjp7IkMxNTQ3ODA0NDUxMTg4Ijp7ImltZ3VybCI6ImltYWdlcy8xLzIwMTkvMDEvYmhkMTEya2dHdmc1eWM2Nkg4ZHlHM1RoeTZsdnlWLmpwZyIsImxpbmt1cmwiOiIiLCJ0aXRsZSI6IiIsInRleHQiOiIifX0sImlkIjoicGljdHVyZXMifSwiTTE1NDc4MDQ0OTM3NzYiOnsicGFyYW1zIjp7InRpdGxlIjoiXHU3MGVkXHU5NTAwXHU1NTQ2XHU1NGMxIiwiaWNvbiI6IiJ9LCJzdHlsZSI6eyJiYWNrZ3JvdW5kIjoiI2ZmZmZmZiIsImNvbG9yIjoiIzAwMDAwMCIsInRleHRhbGlnbiI6ImNlbnRlciIsImZvbnRzaXplIjoiMTciLCJwYWRkaW5ndG9wIjoiNiIsInBhZGRpbmdsZWZ0IjoiNSJ9LCJpZCI6InRpdGxlIn0sIk0xNTQ3ODA0NTIxOTI2Ijp7InBhcmFtcyI6eyJzaG93dGl0bGUiOiIxIiwic2hvd3ByaWNlIjoiMSIsImdvb2RzZGF0YSI6IjEiLCJjYXRlaWQiOiIxNyIsImNhdGVuYW1lIjoiXHU3MzliXHU3NDU5IiwiZ3JvdXBpZCI6IiIsImdyb3VwbmFtZSI6IiIsImdvb2Rzc29ydCI6IjAiLCJnb29kc3Njcm9sbCI6IjAiLCJnb29kc251bSI6IjIwIiwic2hvd2ljb24iOiIwIiwiaWNvbnBvc2l0aW9uIjoibGVmdCB0b3AiLCJwcm9kdWN0cHJpY2UiOiIxIiwic2hvd3Byb2R1Y3RwcmljZSI6IjAiLCJzaG93c2FsZXMiOiIwIiwicHJvZHVjdHByaWNldGV4dCI6Ilx1NTM5Zlx1NGVmNyIsInNhbGVzdGV4dCI6Ilx1OTUwMFx1OTFjZiIsInByb2R1Y3RwcmljZWxpbmUiOiIwIiwic2FsZW91dCI6IjAiLCJzZWVjb21taXNzaW9uIjoiMCIsImNhbnNlZSI6IjAiLCJzZWV0aXRsZSI6IiIsInBhZ2V0eXBlIjoiMjAifSwic3R5bGUiOnsiYmFja2dyb3VuZCI6IiNmM2YzZjMiLCJsaXN0c3R5bGUiOiJibG9jayIsImJ1eXN0eWxlIjoiYnV5YnRuLTEiLCJnb29kc2ljb24iOiJyZWNvbW1hbmQiLCJpY29uc3R5bGUiOiJ0cmlhbmdsZSIsInByaWNlY29sb3IiOiIjZmY1NTU1IiwicHJvZHVjdHByaWNlY29sb3IiOiIjOTk5OTk5IiwiaWNvbnBhZGRpbmd0b3AiOiIwIiwiaWNvbnBhZGRpbmdsZWZ0IjoiMCIsImJ1eWJ0bmNvbG9yIjoiI2ZmNTU1NSIsImljb256b29tIjoiMTAwIiwidGl0bGVjb2xvciI6IiMwMDAwMDAiLCJ0YWdiYWNrZ3JvdW5kIjoiI2ZlNTQ1NSIsInNhbGVzY29sb3IiOiIjOTk5OTk5In0sImRhdGEiOnsiQzE1NDc4MDQ1MjE5MjYiOnsidGh1bWIiOiIuLlwvYWRkb25zXC9ld2VpX3Nob3B2MlwvcGx1Z2luXC9hcHBcL3N0YXRpY1wvaW1hZ2VzXC9kZWZhdWx0XC9nb29kcy0xLmpwZyIsInByaWNlIjoiMjAuMDAiLCJwcm9kdWN0cHJpY2UiOiI5OS4wMCIsInRpdGxlIjoiXHU4ZmQ5XHU5MWNjXHU2NjJmXHU1NTQ2XHU1NGMxXHU2ODA3XHU5ODk4Iiwic2FsZXMiOiIwIiwiZ2lkIjoiIiwiY3R5cGUiOiIxIn0sIkMxNTQ3ODA0NTIxOTI3Ijp7InRodW1iIjoiLi5cL2FkZG9uc1wvZXdlaV9zaG9wdjJcL3BsdWdpblwvYXBwXC9zdGF0aWNcL2ltYWdlc1wvZGVmYXVsdFwvZ29vZHMtMi5qcGciLCJwcmljZSI6IjIwLjAwIiwicHJvZHVjdHByaWNlIjoiOTkuMDAiLCJ0aXRsZSI6Ilx1OGZkOVx1OTFjY1x1NjYyZlx1NTU0Nlx1NTRjMVx1NjgwN1x1OTg5OCIsInNhbGVzIjoiMCIsImdpZCI6IiIsImN0eXBlIjoiMSJ9LCJDMTU0NzgwNDUyMTkyOCI6eyJ0aHVtYiI6Ii4uXC9hZGRvbnNcL2V3ZWlfc2hvcHYyXC9wbHVnaW5cL2FwcFwvc3RhdGljXC9pbWFnZXNcL2RlZmF1bHRcL2dvb2RzLTMuanBnIiwicHJpY2UiOiIyMC4wMCIsInByb2R1Y3RwcmljZSI6Ijk5LjAwIiwic2FsZXMiOiIwIiwidGl0bGUiOiJcdThmZDlcdTkxY2NcdTY2MmZcdTU1NDZcdTU0YzFcdTY4MDdcdTk4OTgiLCJnaWQiOiIiLCJjdHlwZSI6IjAifSwiQzE1NDc4MDQ1MjE5MjkiOnsidGh1bWIiOiIuLlwvYWRkb25zXC9ld2VpX3Nob3B2MlwvcGx1Z2luXC9hcHBcL3N0YXRpY1wvaW1hZ2VzXC9kZWZhdWx0XC9nb29kcy00LmpwZyIsInByaWNlIjoiMjAuMDAiLCJwcm9kdWN0cHJpY2UiOiI5OS4wMCIsInNhbGVzIjoiMCIsInRpdGxlIjoiXHU4ZmQ5XHU5MWNjXHU2NjJmXHU1NTQ2XHU1NGMxXHU2ODA3XHU5ODk4IiwiZ2lkIjoiIiwiY3R5cGUiOiIwIn19LCJpZCI6Imdvb2RzIn19fQ=="));
      
    }
    
	public function get_shopindex()
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$defaults = array(
			'adv'       => array('text' => '幻灯片', 'visible' => 1),
			'search'    => array('text' => '搜索栏', 'visible' => 1),
			'nav'       => array('text' => '导航栏', 'visible' => 1),
			'notice'    => array('text' => '公告栏', 'visible' => 1),
			'cube'      => array('text' => '魔方栏', 'visible' => 1),
			'banner'    => array('text' => '广告栏', 'visible' => 1),
			'recommand' => array('text' => '推荐栏', 'visible' => 1)
			);
		$appsql = '';

		if ($this->iswxapp) {
			$appsql = ' and iswxapp = 1';
		}

		$sorts = $this->iswxapp ? $_W['shopset']['shop']['indexsort_wxapp'] : $_W['shopset']['shop']['indexsort'];
		$sorts = isset($sorts) ? $sorts : $defaults;
		$sorts['recommand'] = array('text' => '系统推荐', 'visible' => 1);
		$advs = pdo_fetchall('select id,advname,link,thumb from ' . tablename('ewei_shop_adv') . ' where uniacid=:uniacid' . $appsql . ' and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$advs = set_medias($advs, 'thumb');
		$navs = pdo_fetchall('select id,navname,url,icon from ' . tablename('ewei_shop_nav') . ' where uniacid=:uniacid' . $appsql . ' and status=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$navs = set_medias($navs, 'icon');
		$cubes = $this->iswxapp ? $_W['shopset']['shop']['cubes_wxapp'] : $_W['shopset']['shop']['cubes'];
		$cubes = set_medias($cubes, 'img');
		$banners = pdo_fetchall('select id,bannername,link,thumb from ' . tablename('ewei_shop_banner') . ' where uniacid=:uniacid' . $appsql . ' and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$banners = set_medias($banners, 'thumb');
		$bannerswipe = $this->iswxapp ? intval($_W['shopset']['shop']['bannerswipe_wxapp']) : intval($_W['shopset']['shop']['bannerswipe']);
		$_W['shopset']['shop']['indexrecommands'] = $this->iswxapp ? $_W['shopset']['shop']['indexrecommands_wxapp'] : $_W['shopset']['shop']['indexrecommands'];

		if (!empty($_W['shopset']['shop']['indexrecommands'])) {
			$goodids = implode(',', $_W['shopset']['shop']['indexrecommands']);

			if (!empty($goodids)) {
				$indexrecommands = pdo_fetchall('select id, title, thumb, marketprice,ispresell,presellprice, productprice, minprice, total from ' . tablename('ewei_shop_goods') . (' where id in( ' . $goodids . ' ) and uniacid=:uniacid and status=1 order by instr(\'' . $goodids . '\',id),displayorder desc'), array(':uniacid' => $uniacid));
				$indexrecommands = set_medias($indexrecommands, 'thumb');

				foreach ($indexrecommands as $key => $value) {
					$indexrecommands[$key]['marketprice'] = (double) $indexrecommands[$key]['marketprice'];
					$indexrecommands[$key]['minprice'] = (double) $indexrecommands[$key]['minprice'];
					$indexrecommands[$key]['presellprice'] = (double) $indexrecommands[$key]['presellprice'];
					$indexrecommands[$key]['productprice'] = (double) $indexrecommands[$key]['productprice'];

					if (0 < $value['ispresell']) {
						$indexrecommands[$key]['minprice'] = $value['presellprice'];
					}
				}
			}
		}

		$goodsstyle = $this->iswxapp ? $_W['shopset']['shop']['goodsstyle_wxapp'] : $_W['shopset']['shop']['goodsstyle'];
		$notices = pdo_fetchall('select id, title, link, thumb from ' . tablename('ewei_shop_notice') . ' where uniacid=:uniacid' . $appsql . ' and status=1 order by displayorder desc limit 5', array(':uniacid' => $uniacid));
		$notices = set_medias($notices, 'thumb');
		$seckillinfo = plugin_run('seckill::getTaskSeckillInfo');
		$copyright = m('common')->getCopyright();
		$newsorts = array();

		foreach ($sorts as $key => $old) {
			$old['type'] = $key;

			if ($key == 'adv') {
				$old['data'] = !empty($advs) ? $advs : array();
			}
			else if ($key == 'nav') {
				$old['data'] = !empty($navs) ? $navs : array();
			}
			else if ($key == 'cube') {
				$old['data'] = !empty($cubes) ? $cubes : array();
			}
			else if ($key == 'banner') {
				$old['data'] = !empty($banners) ? $banners : array();
				$old['bannerswipe'] = !empty($bannerswipe) ? $bannerswipe : array();
			}
			else if ($key == 'notice') {
				$old['data'] = !empty($notices) ? $notices : array();
			}
			else if ($key == 'seckillinfo') {
				$old['data'] = !empty($seckillinfo) ? $seckillinfo : array();
			}
			else {
				if ($key == 'recommand') {
					$old['data'] = !empty($indexrecommands) ? $indexrecommands : array();
				}
			}

			$newsorts[] = $old;
			if ($key == 'notice' && !isset($sorts['seckill'])) {
				$newsorts[] = array('text' => '秒杀栏', 'visible' => 0);
			}
		}

		foreach ($newsorts as $i => &$sortitem) {
			if ($sortitem['data']) {
				foreach ($sortitem['data'] as $ii => $dataitem) {
					if (isset($dataitem['link'])) {
						$link = $this->model->getUrl($dataitem['link']);
						$newsorts[$i]['data'][$ii]['url'] = $link['url'];

						if (!empty($link['vars'])) {
							$newsorts[$i]['data'][$ii]['url_vars'] = $link['vars'];
						}
					}
					else {
						if ($dataitem['url']) {
							$link = $this->model->getUrl($dataitem['url']);
							$newsorts[$i]['data'][$ii]['url'] = $link['url'];

							if (!empty($link['vars'])) {
								$newsorts[$i]['data'][$ii]['url_vars'] = $link['vars'];
							}
						}
					}
				}
			}
			else {
				if ($sortitem['type'] != 'search') {
					unset($newsorts[$i]);
				}
			}
		}

		$result = array('uniacid' => $uniacid, 'sorts' => array_values($newsorts), 'goodsstyle' => $goodsstyle, 'copyright' => !empty($copyright) && !empty($copyright['copyright']) ? $copyright['copyright'] : '', 'customer' => intval($_W['shopset']['app']['customer']));

		if (!empty($result['customer'])) {
			$result['customercolor'] = empty($_W['shopset']['app']['customercolor']) ? '#ff5555' : $_W['shopset']['app']['customercolor'];
		}

		app_json();
	}

	public function get_recommand()
	{
		global $_W;
		global $_GPC;
		$args = array('page' => $_GPC['page'], 'pagesize' => 10, 'isrecommand' => 1, 'order' => 'displayorder desc,createtime desc', 'by' => '');
		$recommand = m('goods')->getList($args);

		if (!empty($recommand['list'])) {
			foreach ($recommand['list'] as &$item) {
				$item['marketprice'] = (double) $item['marketprice'];
				$item['minprice'] = (double) $item['minprice'];
				$item['presellprice'] = (double) $item['presellprice'];
				$item['productprice'] = (double) $item['productprice'];
			}

			unset($item);
		}

		app_json(array('list' => $recommand['list'], 'pagesize' => $args['pagesize'], 'total' => $recommand['total'], 'page' => intval($_GPC['page'])));
	}

	/**
     * 检测是否关闭
     */
	public function check_close()
	{
		global $_W;
		$close = isset($_W['shopset']['close']) ? $_W['shopset']['close'] : array('flag' => 0, 'url' => '', 'detail' => '');
		$close['detail'] = base64_encode($close['detail']);
		app_json(array('close' => $close));
	}

	/**
     * 获取分类
     */
	public function get_category()
	{
		global $_W;
		global $_GPC;
		$refresh = intval($_GPC['refresh']);
		$category_set = $_W['shopset']['category'];
		$category_set['advimg'] = tomedia($category_set['advimg']);
		$level = intval($category_set['level']);
		$category = m('shop')->getCategory();
		$recommands = array();

		foreach ($category['children'] as $k => $v) {
			foreach ($v as $r) {
				if ($r['isrecommand'] == 1) {
					$r['thumb'] = tomedia($r['thumb']);
					$rec = array(
						'id'     => $r['id'],
						'name'   => $r['name'],
						'thumb'  => $r['thumb'],
						'advurl' => $r['advurl'],
						'advimg' => $r['advimg'],
						'child'  => array(),
						'level'  => $r['level']
						);

					if (isset($category['children'][$r['id']])) {
						foreach ($category['children'][$r['id']] as $c) {
							$c['thumb'] = tomedia($c['thumb']);
							$child = array(
								'id'     => $c['id'],
								'name'   => $c['name'],
								'thumb'  => $c['thumb'],
								'advurl' => $c['advurl'],
								'advimg' => $c['advimg'],
								'child'  => array()
								);
							$rec['child'][] = $child;
						}
					}

					$recommands[] = $rec;
				}
			}
		}

		$allcategory = array();

		foreach ($category['parent'] as $p) {
			$p['thumb'] = tomedia($p['thumb']);
			$p['advimg'] = tomedia($p['advimg']);
			$parent = array(
				'id'     => $p['id'],
				'name'   => $p['name'],
				'thumb'  => $p['thumb'],
				'advurl' => $p['advurl'],
				'advimg' => $p['advimg'],
				'child'  => array()
				);

			if (is_array($category['children'][$p['id']])) {
				foreach ($category['children'][$p['id']] as $c) {
					if (!empty($c['thumb'])) {
						$c['thumb'] = tomedia($c['thumb']);
					}

					if (!empty($c['thumb'])) {
						$c['advimg'] = tomedia($c['advimg']);
					}

					if (!empty($c['id'])) {
						$child = array(
							'id'     => $c['id'],
							'name'   => $c['name'],
							'thumb'  => $c['thumb'],
							'advurl' => $c['advurl'],
							'advimg' => $c['advimg'],
							'child'  => array(),
							'level'  => $c['level']
							);
					}

					if (is_array($category['children'][$c['id']])) {
						foreach ($category['children'][$c['id']] as $t) {
							if (!empty($t['thumb'])) {
								$t['thumb'] = tomedia($t['thumb']);
							}

							if (!empty($t['id'])) {
								$child['child'][] = array('id' => $t['id'], 'name' => $t['name'], 'thumb' => $t['thumb'], 'advurl' => $t['advurl'], 'advimg' => $t['advimg']);
							}
						}
					}

					$parent['child'][] = $child;
				}
			}

			$allcategory[] = $parent;
		}

		app_json(array('set' => $category_set, 'recommands' => $recommands, 'category' => $allcategory));
	}

	/**
     * 获取设置
     */
	public function get_set()
	{
		global $_W;
		global $_GPC;
		$sets = array();
		$global_set = m('cache')->getArray('globalset', 'global');

		if (empty($global_set)) {
			$global_set = m('common')->setGlobalSet();
		}

		empty($global_set['trade']['credittext']) && $global_set['trade']['credittext'] = '卡路里';
		empty($global_set['trade']['moneytext']) && $global_set['trade']['moneytext'] = '余额';
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		$openmerch = $merch_plugin && $merch_data['is_openmerch'];
		$sets = array(
			'shop'               => array('name' => $global_set['shop']['name'], 'logo' => tomedia($global_set['shop']['logo']), 'description' => $global_set['shop']['description'], 'img' => tomedia($global_set['shop']['img'])),
			'share'              => array('title' => empty($global_set['share']['title']) ? $global_set['shop']['name'] : $global_set['share']['title'], 'img' => empty($global_set['share']['icon']) ? tomedia($global_set['shop']['logo']) : tomedia($global_set['share']['icon']), 'desc' => empty($global_set['share']['desc']) ? $global_set['shop']['description'] : $global_set['share']['desc'], 'link' => empty($global_set['share']['url']) ? mobileUrl('', array('appfrom' => 1), true) : $global_set['share']['url']),
			'trade'              => array('closerecharge' => intval($global_set['trade']['closerecharge']), 'minimumcharge' => floatval($global_set['trade']['minimumcharge']), 'withdraw' => intval($global_set['trade']['withdraw']), 'withdrawmoney' => floatval($global_set['trade']['withdrawmoney']), 'closecomment' => intval($global_set['trade']['withdraw']), 'closecommentshow' => intval($global_set['trade']['closecommentshow'])),
			'payset'             => array('weixin' => intval($global_set['pay']['weixin']), 'alipay' => intval($global_set['pay']['alipay']), 'credit' => intval($global_set['pay']['credit']), 'cash' => intval($global_set['pay']['cash'])),
			'contact'            => array('phone' => isset($global_set['contact']['phone']) ? $global_set['contact']['phone'] : '', 'province' => isset($global_set['contact']['phone']) ? $global_set['contact']['province'] : '', 'city' => isset($global_set['contact']['phone']) ? $global_set['contact']['city'] : '', 'address' => isset($global_set['contact']['phone']) ? $global_set['contact']['address'] : ''),
			'menu'               => $this->model->diyMenu('shop'),
			'cancelorderreasons' => array('不取消了', '我不想买了', '信息填写错误，重新拍', '同城见面交易', '其他原因'),
			'openmerch'          => $openmerch,
			'texts'              => array('credittext' => $global_set['trade']['credittext'], 'moneytext' => $global_set['trade']['moneytext'])
			);
		app_json(array('sets' => $sets));
	}

	public function get_areas()
	{
		$areas = m('common')->getAreas();
		app_json(array('areas' => $areas));
	}

	public function get_nopayorder()
	{
		global $_W;
		global $_GPC;
		$hasinfo = 0;
		$trade = m('common')->getSysset('trade');

		if (empty($trade['shop_strengthen'])) {
			$order = pdo_fetch('select id,price  from ' . tablename('ewei_shop_order') . ' where uniacid=:uniacid and status = 0 and paytype<>3 and openid=:openid order by createtime desc limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));

			if (!empty($order)) {
				$goods = pdo_fetchall('select g.*,og.total as totals  from ' . tablename('ewei_shop_order_goods') . ' og inner join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id   where og.uniacid=:uniacid    and og.orderid=:orderid  limit 3', array(':uniacid' => $_W['uniacid'], ':orderid' => $order['id']));
				$goods = set_medias($goods, 'thumb');
				$goodstotal = pdo_fetchcolumn('select COUNT(*)  from ' . tablename('ewei_shop_order_goods') . ' og inner join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id   where og.uniacid=:uniacid    and og.orderid=:orderid ', array(':uniacid' => $_W['uniacid'], ':orderid' => $order['id']));

				if (!empty($goodstotal)) {
					$hasinfo = 1;
				}
			}
		}

		app_json(array('hasinfo' => $hasinfo, 'order' => $order, 'goods' => $goods, 'goodstotal' => intval($goodstotal)));
	}

	public function get_hasnewcoupon()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$member = m('member')->getMember($_W['openid']);
		$hasnewcoupon = intval($member['hasnewcoupon']);
		pdo_update('ewei_shop_member', array('hasnewcoupon' => 0), array('openid' => $openid, 'uniacid' => $_W['uniacid']));
		app_json(array('hasnewcoupon' => $hasnewcoupon, 'o' => $openid));
	}

	public function get_cpinfos()
	{
		global $_W;
		global $_GPC;
		$cpinfos = false;

		if (com('coupon')) {
			$cpinfos = com('coupon')->getInfo();
		}

		$hascpinfos = 0;

		if ($cpinfos) {
			$hascpinfos = 1;

			foreach ($cpinfos as &$cpinfo) {
				$enough = (double) $cpinfo['enough'];

				if (empty($enough)) {
					$cpinfo['enoughtext'] = '无金额门槛';
				}
				else {
					$cpinfo['enoughtext'] = '满' . $enough . '元可用';
				}

				if ($cpinfo['timelimit'] == 0 && $cpinfo['timedays'] == 0) {
					$cpinfo['timelimittext'] = '永久有效';
				}
				else if ($cpinfo['timelimit'] == 0) {
					$cpinfo['timelimittext'] = '有效期: ' . date('Y-m-d', TIMESTAMP) . '至' . date('Y-m-d', TIMESTAMP + 60 * 60 * 24 * intval($cpinfo['timedays']));
				}
				else {
					$cpinfo['timelimittext'] = '有效期: ' . date('Y-m-d', $cpinfo['timestart']) . '至' . date('Y-m-d', $cpinfo['timeend']);
				}

				if ($cpinfo['backtype'] == 0) {
					$cpinfo['t1'] = '元';
					$cpinfo['t2'] = floatval($cpinfo['deduct']);
				}
				else if ($cpinfo['backtype'] == 1) {
					$cpinfo['t1'] = '折';
					$cpinfo['t2'] = floatval($cpinfo['discount']);
				}
				else {
					if ($cpinfo['backtype'] == 2) {
						if (!empty($cpinfo['backredpack'])) {
							$cpinfo['t1'] = '元';
							$cpinfo['t2'] = floatval($cpinfo['backredpack']);
						}
						else if (!empty($cpinfo['backmoney'])) {
							$cpinfo['t1'] = '余额';
							$cpinfo['t2'] = floatval($cpinfo['backmoney']);
						}
						else if (!empty($cpinfo['backcredit'])) {
							$cpinfo['t1'] = '卡路里';
							$cpinfo['t2'] = floatval($cpinfo['backcredit']);
						}
						else {
							$cpinfo['t1'] = '元';
							$cpinfo['t2'] = 0;
						}
					}
				}

				$cpinfo['abc'] = 111111;
			}

			unset($cpinfo);
		}

		app_json(array('hascpinfos' => $hascpinfos, 'cpinfos' => $cpinfos));
	}
}

?>
